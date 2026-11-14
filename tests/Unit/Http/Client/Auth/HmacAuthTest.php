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
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Auth\HmacAuth;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class HmacAuthTest extends TestCase
{
    private static function apply(
        ClientRequestInterface $request,
        string $algorithm = 'sha256',
    ): ClientRequestInterface {
        return new HmacAuth(
            serializerManager: SerializerManagerFactory::create(),
            key: 'secret',
            algorithm: $algorithm,
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        )->apply($request, AuthClient::create(new ScriptedTransport()));
    }

    private static function signature(
        string $payload,
        string $algorithm = 'sha256',
    ): string {
        return \hash_hmac($algorithm, $payload, 'secret');
    }

    public function testSignsMethodTargetTimestampAndBody(): void
    {
        $request = self::apply(
            new ClientRequest(
                method: Method::POST,
                url: 'https://api.example/v1/items?b=2',
                body: new JsonBody(
                    [
                        'a' => 1,
                    ],
                ),
            ),
        );

        self::assertSame('1000', AuthClient::header($request, 'X-Timestamp'));
        self::assertSame(
            self::signature("POST\n/v1/items?b=2\n1000\n" . \hash('sha256', '{"a":1}')),
            AuthClient::header($request, 'X-Signature'),
        );
    }

    public function testEmptyPathAndBody(): void
    {
        $request = self::apply(
            request: new ClientRequest(Method::GET, 'https://api.example'),
            algorithm: 'sha512',
        );

        self::assertSame(
            self::signature("GET\n/\n1000\n" . \hash('sha512', ''), 'sha512'),
            AuthClient::header($request, 'X-Signature'),
        );
    }

    public function testCustomHeaderNames(): void
    {
        $request = new HmacAuth(
            serializerManager: SerializerManagerFactory::create(),
            key: 'secret',
            signatureHeader: 'X-Sig',
            timestampHeader: 'X-Time',
        )->apply(
            new ClientRequest(Method::GET, 'https://api.example/'),
            AuthClient::create(new ScriptedTransport()),
        );

        self::assertNotNull(AuthClient::header($request, 'X-Sig'));
        self::assertNotNull(AuthClient::header($request, 'X-Time'));
        self::assertNull(AuthClient::header($request, 'X-Signature'));
    }

    public function testReadsAndRewindsStreams(): void
    {
        $stream = ResourceStream::fromTemporary();

        $stream->write('payload');

        $request = self::apply(
            new ClientRequest(
                method: Method::PUT,
                url: 'https://api.example/x',
                body: new RawBody($stream),
            ),
        );

        self::assertSame(
            self::signature("PUT\n/x\n1000\n" . \hash('sha256', 'payload')),
            AuthClient::header($request, 'X-Signature'),
        );

        self::assertSame('payload', $stream->getContents());
    }

    public function testOneShotStreamsCannotBeSigned(): void
    {
        try {
            self::apply(
                new ClientRequest(
                    method: Method::PUT,
                    url: 'https://api.example/x',
                    body: new RawBody(
                        Stream::fromGenerator(
                            static function (): \Generator {
                                yield 'once';
                            },
                        ),
                    ),
                ),
            );

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::REPLAY, $exception->failure);
        }
    }
}
