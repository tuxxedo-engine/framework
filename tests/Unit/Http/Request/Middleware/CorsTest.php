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

namespace Unit\Http\Request\Middleware;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Request\Middleware\RecordingMiddleware;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\Cors;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class CorsTest extends TestCase
{
    private function makeRequest(
        Method $method = Method::GET,
        ?StubHeaderContext $headers = null,
    ): Request {
        return new Request(
            headers: $headers ?? new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: $method,
        );
    }

    private function findHeader(
        ResponseInterface $response,
        string $name,
    ): ?string {
        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, $name) === 0) {
                return $header->value;
            }
        }

        return null;
    }

    public function testConstructorThrowsWhenWildcardCombinedWithCredentials(): void
    {
        self::expectException(HttpException::class);

        new Cors(
            allowedOrigins: [
                '*',
            ],
            allowCredentials: true,
        );
    }

    public function testHandlePassesThroughWhenOriginAbsent(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandleEchoesWildcardForActualRequestUnderWildcardNoCredentials(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            '*',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandleOmitsVaryUnderWildcardOrigin(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Vary',
            ),
        );
    }

    public function testHandleEchoesOriginForActualRequestUnderExactMatch(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
                'https://app.example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://app.example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'https://app.example.com',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandleAddsVaryOriginForActualRequestUnderExactMatch(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'Origin',
            $this->findHeader(
                response: $response,
                name: 'Vary',
            ),
        );
    }

    public function testHandleSkipsCorsDecorationWhenOriginNotAllowed(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://evil.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandlePassesThroughOptionsWithoutPreflightHeader(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertSame(
            '*',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandleEmitsPreflightAllowOriginWildcard(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertSame(ResponseCode::NO_CONTENT, $response->responseCode);
        self::assertSame(
            '*',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandlePreflightOmitsVaryUnderWildcard(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Vary',
            ),
        );
    }

    public function testHandleEmitsPreflightAllowMethodsHeader(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'GET, HEAD, POST, PUT, PATCH, DELETE',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Methods',
            ),
        );
    }

    public function testHandleEmitsPreflightAllowHeadersHeader(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors())->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'Content-Type, Authorization, X-Requested-With',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Headers',
            ),
        );
    }

    public function testHandleEmitsPreflightMaxAgeHeader(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            maxAge: 1234,
        ))->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            '1234',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Max-Age',
            ),
        );
    }

    public function testHandleEmitsPreflightVaryUnderExactOrigin(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'Origin',
            $this->findHeader(
                response: $response,
                name: 'Vary',
            ),
        );
    }

    public function testHandleEmitsPreflightAllowCredentialsWhenConfigured(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
            allowCredentials: true,
        ))->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'true',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Credentials',
            ),
        );
    }

    public function testHandleEmitsPreflightSkipsAcaoWhenOriginNotAllowed(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://evil.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Origin',
            ),
        );
    }

    public function testHandlePreflightSkipsVaryWhenOriginNotAllowed(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                method: Method::OPTIONS,
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://evil.com',
                        'Access-Control-Request-Method' => 'POST',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Vary',
            ),
        );
    }

    public function testHandleEmitsExposedHeadersOnActualRequest(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
            exposedHeaders: [
                'X-Custom',
                'X-RateLimit-Remaining',
            ],
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'X-Custom, X-RateLimit-Remaining',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Expose-Headers',
            ),
        );
    }

    public function testHandleOmitsExposedHeadersWhenNoneConfigured(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Expose-Headers',
            ),
        );
    }

    public function testHandleEmitsAllowCredentialsOnActualRequest(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cors(
            allowedOrigins: [
                'https://example.com',
            ],
            allowCredentials: true,
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Origin' => 'https://example.com',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            'true',
            $this->findHeader(
                response: $response,
                name: 'Access-Control-Allow-Credentials',
            ),
        );
    }
}
