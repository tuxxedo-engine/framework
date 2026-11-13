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
use Tuxxedo\Http\Client\Auth\JwtAssertionAuth;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Method;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;

class JwtAssertionAuthTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function claims(
        ClientRequestInterface $request,
    ): array {
        \parse_str(AuthClient::body($request), $fields);

        $assertion = $fields['assertion'] ?? null;

        self::assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $fields['grant_type'] ?? null);
        self::assertIsString($assertion);

        $segments = \explode('.', $assertion);
        $claims = \json_decode(
            json: (string) \base64_decode(\strtr($segments[1] ?? '', '-_', '+/'), true),
            associative: true,
            flags: \JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($claims);

        /** @var array<string, mixed> $claims */
        return $claims;
    }

    /**
     * @param list<string> $scopes
     */
    private static function auth(
        ?string $subject = null,
        array $scopes = [],
    ): JwtAssertionAuth {
        return new JwtAssertionAuth(
            tokenUrl: 'https://auth.example/token',
            issuer: 'service@example',
            audience: 'https://auth.example/token',
            key: new SymmetricKey(\str_repeat('k', 32)),
            algorithm: Algorithm::HS256,
            subject: $subject,
            scopes: $scopes,
            lifetime: Duration::fromMinutes(5),
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        );
    }

    public function testExchangesAnAssertion(): void
    {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'abc',
                    ],
                ),
            ],
        );

        $client = AuthClient::create($transport);
        $auth = self::auth(
            subject: 'user@example',
            scopes: [
                'read',
                'write',
            ],
        );

        $headers = [];

        for ($i = 0; $i < 2; $i++) {
            $headers[] = AuthClient::header(
                $auth->apply(new ClientRequest(Method::GET, 'https://api.example/'), $client),
                'Authorization',
            );
        }

        \parse_str(AuthClient::body($transport->requests[0]), $fields);

        self::assertSame(
            [
                'Bearer abc',
                'Bearer abc',
            ],
            $headers,
        );

        self::assertSame(1, \sizeof($transport->requests));
        self::assertNull(AuthClient::header($transport->requests[0], 'Authorization'));
        self::assertSame('read write', $fields['scope'] ?? null);
        self::assertSame(
            [
                'iss' => 'service@example',
                'aud' => 'https://auth.example/token',
                'iat' => 1_000,
                'exp' => 1_300,
                'sub' => 'user@example',
                'scope' => 'read write',
            ],
            self::claims($transport->requests[0]),
        );
    }

    public function testRefreshDropsTheTokenOnUnauthorized(): void
    {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'abc',
                    ],
                ),
                ClientResponse::json(
                    [
                        'access_token' => 'def',
                    ],
                ),
            ],
        );

        $client = AuthClient::create($transport);
        $auth = self::auth();
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
        self::assertSame(
            [
                'iss' => 'service@example',
                'aud' => 'https://auth.example/token',
                'iat' => 1_000,
                'exp' => 1_300,
            ],
            self::claims($transport->requests[1]),
        );
    }
}
