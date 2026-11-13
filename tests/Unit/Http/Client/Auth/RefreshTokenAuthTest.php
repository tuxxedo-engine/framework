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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\AuthClient;
use Support\Http\Client\ScriptedTransport;
use Tuxxedo\Http\Client\Auth\AccessToken;
use Tuxxedo\Http\Client\Auth\MemoryTokenStore;
use Tuxxedo\Http\Client\Auth\RefreshTokenAuth;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class RefreshTokenAuthTest extends TestCase
{
    private static function auth(
        MemoryTokenStore $store,
    ): RefreshTokenAuth {
        return new RefreshTokenAuth(
            tokenUrl: 'https://auth.example/token',
            clientId: 'id',
            store: $store,
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        );
    }

    public function testUsesTheStoredToken(): void
    {
        $transport = new ScriptedTransport();
        $request = self::auth(new MemoryTokenStore(new AccessToken('abc')))->apply(
            new ClientRequest(Method::GET, 'https://api.example/'),
            AuthClient::create($transport),
        );

        self::assertSame('Bearer abc', AuthClient::header($request, 'Authorization'));
        self::assertSame([], $transport->requests);
    }

    public function testRefreshesAnExpiredToken(): void
    {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'def',
                        'expires_in' => 3600,
                    ],
                ),
            ],
        );

        $store = new MemoryTokenStore(
            new AccessToken(
                accessToken: 'abc',
                expiresAt: Instant::fromUnixTimestamp(900),
                refreshToken: 'refresh',
            ),
        );

        $request = self::auth($store)->apply(
            new ClientRequest(Method::GET, 'https://api.example/'),
            AuthClient::create($transport),
        );

        self::assertSame('Bearer def', AuthClient::header($request, 'Authorization'));
        self::assertSame('grant_type=refresh_token&refresh_token=refresh&client_id=id', AuthClient::body($transport->requests[0]));
        self::assertSame('def', $store->get()?->accessToken);
        self::assertSame('refresh', $store->get()->refreshToken);
    }

    public function testRefreshForcesARefreshOnUnauthorized(): void
    {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'def',
                        'refresh_token' => 'rotated',
                    ],
                ),
            ],
        );

        $client = AuthClient::create($transport);
        $store = new MemoryTokenStore(
            new AccessToken(
                accessToken: 'abc',
                refreshToken: 'refresh',
            ),
        );

        $auth = self::auth($store);
        $refreshes = [
            $auth->refresh(ClientResponse::empty(403), $client),
            $auth->refresh(ClientResponse::empty(401), $client),
        ];

        $headers = [];

        for ($i = 0; $i < 2; $i++) {
            $headers[] = AuthClient::header(
                $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client),
                'Authorization',
            );
        }

        self::assertSame(
            [
                false,
                true,
            ],
            $refreshes,
        );

        self::assertSame(
            [
                'Bearer def',
                'Bearer def',
            ],
            $headers,
        );

        self::assertSame(1, \sizeof($transport->requests));
        self::assertSame('rotated', $store->get()?->refreshToken);
    }

    public function testRefreshIsDeclinedWithoutARefreshToken(): void
    {
        $client = AuthClient::create(new ScriptedTransport());

        self::assertFalse(self::auth(new MemoryTokenStore())->refresh(ClientResponse::empty(401), $client));
        self::assertFalse(self::auth(new MemoryTokenStore(new AccessToken('abc')))->refresh(ClientResponse::empty(401), $client));
    }

    /**
     * @return \Generator<array{0: MemoryTokenStore}>
     */
    public static function missingTokenDataProvider(): \Generator
    {
        yield [
            new MemoryTokenStore(),
        ];

        yield [
            new MemoryTokenStore(
                new AccessToken(
                    accessToken: 'abc',
                    expiresAt: Instant::fromUnixTimestamp(900),
                ),
            ),
        ];
    }

    #[DataProvider('missingTokenDataProvider')]
    public function testMissingTokens(
        MemoryTokenStore $store,
    ): void {
        try {
            self::auth($store)->apply(
                new ClientRequest(Method::GET, 'https://api.example/'),
                AuthClient::create(new ScriptedTransport()),
            );

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::AUTH, $exception->failure);
            self::assertNull($exception->response);
        }
    }
}
