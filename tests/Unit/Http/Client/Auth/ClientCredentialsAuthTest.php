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

namespace Unit\Http\Client\Auth;

use PHPUnit\Framework\TestCase;
use Support\Http\Client\AuthClient;
use Support\Http\Client\ScriptedTransport;
use Tuxxedo\Http\Client\Auth\ClientCredentialsAuth;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Response\ClientResponse;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\AdvancingClock;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class ClientCredentialsAuthTest extends TestCase
{
    private static function token(
        string $accessToken,
    ): ClientResponse {
        return ClientResponse::json(
            [
                'access_token' => $accessToken,
                'expires_in' => 3600,
            ],
        );
    }

    public function testFetchesAndCachesTheToken(): void
    {
        $transport = new ScriptedTransport(
            [
                self::token('abc'),
            ],
        );

        $client = AuthClient::create($transport);
        $auth = new ClientCredentialsAuth(
            tokenUrl: 'https://auth.example/token',
            clientId: 'id',
            clientSecret: 'secret',
            scopes: [
                'read',
                'write',
            ],
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        );

        $headers = [];

        for ($i = 0; $i < 2; $i++) {
            $headers[] = AuthClient::header(
                $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client),
                'Authorization',
            );
        }

        self::assertSame(
            [
                'Bearer abc',
                'Bearer abc',
            ],
            $headers,
        );

        self::assertSame(1, \sizeof($transport->requests));
        self::assertSame('grant_type=client_credentials&scope=read+write', AuthClient::body($transport->requests[0]));
        self::assertSame('Basic ' . \base64_encode('id:secret'), AuthClient::header($transport->requests[0], 'Authorization'));
    }

    public function testFetchesAgainOnceExpired(): void
    {
        $transport = new ScriptedTransport(
            [
                self::token('abc'),
                self::token('def'),
            ],
        );

        $client = AuthClient::create($transport);
        $auth = new ClientCredentialsAuth(
            tokenUrl: 'https://auth.example/token',
            clientId: 'id',
            clientSecret: 'secret',
            credentialsInBody: true,
            clock: new AdvancingClock(Instant::fromUnixTimestamp(1_000), Duration::fromHours(2)),
        );

        $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client);

        $request = $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client);

        self::assertSame('Bearer def', AuthClient::header($request, 'Authorization'));
        self::assertSame('grant_type=client_credentials&client_id=id&client_secret=secret', AuthClient::body($transport->requests[1]));
    }

    public function testRefreshDropsTheTokenOnUnauthorized(): void
    {
        $transport = new ScriptedTransport(
            [
                self::token('abc'),
                self::token('def'),
            ],
        );

        $client = AuthClient::create($transport);
        $auth = new ClientCredentialsAuth(
            tokenUrl: 'https://auth.example/token',
            clientId: 'id',
            clientSecret: 'secret',
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        );

        $refreshes = [
            $auth->refresh(ClientResponse::empty(401), $client),
        ];

        $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client);

        $refreshes[] = $auth->refresh(ClientResponse::empty(403), $client);
        $refreshes[] = $auth->refresh(ClientResponse::empty(401), $client);

        $request = $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client);

        self::assertSame(
            [
                false,
                false,
                true,
            ],
            $refreshes,
        );

        self::assertSame('Bearer def', AuthClient::header($request, 'Authorization'));
    }
}
