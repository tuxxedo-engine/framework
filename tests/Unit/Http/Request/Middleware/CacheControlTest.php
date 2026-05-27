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
use Support\Http\Request\Context\StubServerContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Request\Middleware\RecordingMiddleware;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Middleware\CacheControl;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class CacheControlTest extends TestCase
{
    private function makeRequest(): Request
    {
        return new Request(
            server: new StubServerContext(),
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
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

    public function testHandleEmitsMaxAgeDirective(): void
    {
        $response = (new CacheControl(
            maxAge: 3600,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'max-age=3600',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsSMaxAgeDirective(): void
    {
        $response = (new CacheControl(
            sMaxAge: 7200,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            's-maxage=7200',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsPublicDirective(): void
    {
        $response = (new CacheControl(
            public: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'public',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsPrivateDirective(): void
    {
        $response = (new CacheControl(
            private: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'private',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsNoCacheDirective(): void
    {
        $response = (new CacheControl(
            noCache: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'no-cache',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsNoStoreDirective(): void
    {
        $response = (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'no-store',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsMustRevalidateDirective(): void
    {
        $response = (new CacheControl(
            mustRevalidate: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'must-revalidate',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsProxyRevalidateDirective(): void
    {
        $response = (new CacheControl(
            proxyRevalidate: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'proxy-revalidate',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsImmutableDirective(): void
    {
        $response = (new CacheControl(
            immutable: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'immutable',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsStaleWhileRevalidateDirective(): void
    {
        $response = (new CacheControl(
            staleWhileRevalidate: 60,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'stale-while-revalidate=60',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleEmitsStaleIfErrorDirective(): void
    {
        $response = (new CacheControl(
            staleIfError: 120,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'stale-if-error=120',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleCombinesMultipleDirectives(): void
    {
        $response = (new CacheControl(
            maxAge: 3600,
            sMaxAge: 7200,
            public: true,
            mustRevalidate: true,
            staleWhileRevalidate: 60,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'public, must-revalidate, max-age=3600, s-maxage=7200, stale-while-revalidate=60',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleThrowsWhenPublicAndPrivateBothTrue(): void
    {
        self::expectException(HttpException::class);

        (new CacheControl(
            public: true,
            private: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );
    }

    public function testHandlePreservesExistingCacheControlByDefault(): void
    {
        $existing = (new Response())->withHeader(
            new Header('Cache-Control', 'public, max-age=3600'),
        );

        $response = (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame(
            'public, max-age=3600',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleOverridesExistingCacheControlWhenOnlyIfMissingIsFalse(): void
    {
        $existing = (new Response())->withHeader(
            new Header('Cache-Control', 'public, max-age=3600'),
        );

        $response = (new CacheControl(
            noStore: true,
            onlyIfMissing: false,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame(
            'no-store',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleAddsCacheControlWhenAbsentAndOnlyIfMissingIsTrue(): void
    {
        $response = (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'no-store',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleMatchesExistingCacheControlCaseInsensitively(): void
    {
        $existing = (new Response())->withHeader(
            new Header('cache-control', 'public, max-age=3600'),
        );

        $response = (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame(
            'public, max-age=3600',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleAddsCacheControlWhenResponseHasOtherHeadersButNoCacheControl(): void
    {
        $existing = (new Response())->withHeader(
            new Header('Content-Type', 'text/plain'),
        );

        $response = (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame(
            'no-store',
            $this->findHeader(
                response: $response,
                name: 'Cache-Control',
            ),
        );
    }

    public function testHandleCallsNext(): void
    {
        $next = new RecordingMiddleware();

        (new CacheControl(
            noStore: true,
        ))->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
