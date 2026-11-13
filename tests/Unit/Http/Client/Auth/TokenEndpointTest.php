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
use Tuxxedo\Http\Client\Auth\TokenEndpoint;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\Instant;

class TokenEndpointTest extends TestCase
{
    public function testRequestsWithBasicCredentials(): void
    {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'abc',
                        'token_type' => 'MAC',
                        'expires_in' => 60,
                        'refresh_token' => 'new',
                    ],
                ),
            ],
        );

        $token = new TokenEndpoint(
            url: 'https://auth.example/token',
            clientId: 'id:1',
            clientSecret: 's&cret',
        )->request(
            httpClient: AuthClient::create($transport),
            fields: [
                'grant_type' => 'client_credentials',
            ],
            now: Instant::fromUnixTimestamp(1_000),
            refreshToken: 'old',
        );

        $request = $transport->requests[0];

        self::assertSame(Method::POST, $request->method);
        self::assertSame('https://auth.example/token', (string) $request->url);
        self::assertSame('application/json', AuthClient::header($request, 'Accept'));
        self::assertSame('Basic ' . \base64_encode('id%3A1:s%26cret'), AuthClient::header($request, 'Authorization'));
        self::assertSame('grant_type=client_credentials', AuthClient::body($request));
        self::assertSame('abc', $token->accessToken);
        self::assertSame('MAC', $token->tokenType);
        self::assertSame(1_060, $token->expiresAt?->toUnixTimestamp());
        self::assertSame('new', $token->refreshToken);
    }

    /**
     * @return \Generator<array{0: TokenEndpoint, 1: string}>
     */
    public static function credentialsDataProvider(): \Generator
    {
        yield [
            new TokenEndpoint(
                url: 'https://auth.example/token',
                clientId: 'id',
                clientSecret: 'secret',
                credentialsInBody: true,
            ),
            'grant_type=x&client_id=id&client_secret=secret',
        ];

        yield [
            new TokenEndpoint(
                url: 'https://auth.example/token',
                clientId: 'id',
            ),
            'grant_type=x&client_id=id',
        ];

        yield [
            new TokenEndpoint('https://auth.example/token'),
            'grant_type=x',
        ];
    }

    #[DataProvider('credentialsDataProvider')]
    public function testCredentialsInTheBody(
        TokenEndpoint $endpoint,
        string $body,
    ): void {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'abc',
                    ],
                ),
            ],
        );

        $token = $endpoint->request(
            httpClient: AuthClient::create($transport),
            fields: [
                'grant_type' => 'x',
            ],
            now: Instant::fromUnixTimestamp(1_000),
            refreshToken: 'kept',
        );

        self::assertNull(AuthClient::header($transport->requests[0], 'Authorization'));
        self::assertSame($body, AuthClient::body($transport->requests[0]));
        self::assertSame('Bearer', $token->tokenType);
        self::assertNull($token->expiresAt);
        self::assertSame('kept', $token->refreshToken);
    }

    /**
     * @return \Generator<array{0: mixed, 1: int|null}>
     */
    public static function expiresInDataProvider(): \Generator
    {
        yield [
            '120',
            1_120,
        ];

        yield [
            -5,
            1_000,
        ];

        yield [
            '1.5',
            null,
        ];

        yield [
            null,
            null,
        ];
    }

    #[DataProvider('expiresInDataProvider')]
    public function testExpiresIn(
        mixed $expiresIn,
        ?int $expected,
    ): void {
        $transport = new ScriptedTransport(
            [
                ClientResponse::json(
                    [
                        'access_token' => 'abc',
                        'token_type' => '',
                        'expires_in' => $expiresIn,
                        'refresh_token' => '',
                    ],
                ),
            ],
        );

        $token = new TokenEndpoint('https://auth.example/token')->request(
            httpClient: AuthClient::create($transport),
            fields: [],
            now: Instant::fromUnixTimestamp(1_000),
        );

        self::assertSame($expected, $token->expiresAt?->toUnixTimestamp());
        self::assertSame('Bearer', $token->tokenType);
        self::assertNull($token->refreshToken);
    }

    /**
     * @return \Generator<array{0: ClientResponse, 1: bool}>
     */
    public static function failureDataProvider(): \Generator
    {
        yield [
            ClientResponse::json(
                data: [
                    'error' => 'invalid_client',
                ],
                statusCode: 401,
            ),
            false,
        ];

        yield [
            ClientResponse::json(
                data: [
                    'access_token' => 'abc',
                ],
                statusCode: 199,
            ),
            false,
        ];

        yield [
            ClientResponse::text('{'),
            true,
        ];

        yield [
            ClientResponse::json('abc'),
            false,
        ];

        yield [
            ClientResponse::json(
                [
                    'access_token' => '',
                ],
            ),
            false,
        ];

        yield [
            ClientResponse::json(
                [
                    'access_token' => 1,
                ],
            ),
            false,
        ];
    }

    #[DataProvider('failureDataProvider')]
    public function testFailures(
        ClientResponse $response,
        bool $hasPrevious,
    ): void {
        try {
            new TokenEndpoint('https://auth.example/token')->request(
                httpClient: AuthClient::create(
                    new ScriptedTransport(
                        [
                            $response,
                        ],
                    ),
                ),
                fields: [],
                now: Instant::fromUnixTimestamp(1_000),
            );

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::AUTH, $exception->failure);
            self::assertSame($response, $exception->response);
            self::assertSame($hasPrevious, $exception->getPrevious() instanceof \JsonException);
        }
    }
}
