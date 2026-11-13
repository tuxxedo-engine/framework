<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Http\Client;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\HttpRules;
use Tuxxedo\Http\Method;

class HttpRulesTest extends TestCase
{
    private static function request(
        string $url = 'https://api.example/x',
    ): ClientRequestInterface {
        return new ClientRequest(Method::GET, $url);
    }

    private static function deniedBy(
        HttpRules $rules,
        string $url,
    ): ?string {
        try {
            $rules->evaluate(self::request($url));
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RULE, $exception->failure);

            return $exception->getMessage();
        }

        return null;
    }

    private static function raw(
        ClientResponseInterface|HttpClientException|null $result,
    ): string {
        self::assertInstanceOf(ClientResponseInterface::class, $result);

        return $result->body->getRaw();
    }

    public function testFirstMatchingRuleDecides(): void
    {
        $rules = new HttpRules()
            ->allow('api.example/public/*')
            ->deny('api.example', '*.internal');

        self::assertNull($rules->evaluate(self::request('https://api.example/public/x')));
        self::assertNull($rules->evaluate(self::request('https://other.example/')));
        self::assertNotNull(self::deniedBy($rules, 'https://api.example/x'));
        self::assertNotNull(self::deniedBy($rules, 'https://db.internal/'));
    }

    public function testDenyUnmatched(): void
    {
        $rules = new HttpRules()->allow('api.example')->denyUnmatched();

        self::assertNull($rules->evaluate(self::request()));
        self::assertStringContainsString('matched no rule', (string) self::deniedBy($rules, 'https://other.example/'));
    }

    public function testRespondWithAResponseAClosureOrASequence(): void
    {
        $response = ClientResponse::text('a');
        $rules = new HttpRules()
            ->respond('api.example/fixed', $response)
            ->respond('api.example/closure', static fn (ClientRequestInterface $request): ClientResponse => ClientResponse::text($request->url->path))
            ->respond(
                'api.example/sequence',
                [
                    ClientResponse::text('1'),
                    ClientResponse::text('2'),
                ],
            );

        $sequence = [];

        for ($i = 0; $i < 3; $i++) {
            $sequence[] = $rules->evaluate(self::request('https://api.example/sequence'));
        }

        self::assertSame($response, $rules->evaluate(self::request('https://api.example/fixed')));
        self::assertSame('/closure', self::raw($rules->evaluate(self::request('https://api.example/closure'))));
        self::assertSame('1', self::raw($sequence[0]));
        self::assertSame('2', self::raw($sequence[1]));
        self::assertInstanceOf(HttpClientException::class, $sequence[2]);
        self::assertSame(HttpClientFailure::RULE, $sequence[2]->failure);
    }

    public function testFail(): void
    {
        $result = new HttpRules()
            ->fail('api.example', static fn (ClientRequestInterface $request): HttpClientException => HttpClientException::fromTimeout($request, HttpClientTimeout::CONNECT))
            ->evaluate(self::request());

        self::assertInstanceOf(HttpClientException::class, $result);
        self::assertSame(HttpClientFailure::TIMEOUT, $result->failure);
    }

    public function testDenyPrivateNetworks(): void
    {
        $rules = new HttpRules()->denyPrivateNetworks();

        self::assertStringContainsString('"127.0.0.1" is a private network address', (string) self::deniedBy($rules, 'http://127.0.0.1/'));
        self::assertNotNull(self::deniedBy($rules, 'http://[::1]/'));
        self::assertNull($rules->evaluate(self::request('http://93.184.216.34/')));
        self::assertNull($rules->evaluate(self::request('http://localhost/')));
        self::assertFalse($rules->allowsAddress('192.168.1.1'));
        self::assertTrue($rules->allowsAddress('93.184.216.34'));
        self::assertTrue(new HttpRules()->allowsAddress('10.0.0.1'));
    }

    public function testRulesAreImmutable(): void
    {
        $rules = new HttpRules();
        $denying = $rules->deny('api.example');

        self::assertNotSame($rules, $denying);
        self::assertNull($rules->evaluate(self::request()));
    }
}
