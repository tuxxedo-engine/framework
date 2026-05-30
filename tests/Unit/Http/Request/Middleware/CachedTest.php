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
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\Cached;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class CachedTest extends TestCase
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

    public function testHandlePassesThroughForPostMethod(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(
                method: Method::POST,
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"abc123"',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull($this->findHeader($response, 'ETag'));
    }

    public function testHandlePassesThroughForPutMethod(): void
    {
        $next = new RecordingMiddleware();

        (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(
                method: Method::PUT,
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"abc123"',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleActivatesForHeadMethod(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(
                method: Method::HEAD,
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"abc123"',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertSame(ResponseCode::NOT_MODIFIED, $response->responseCode);
    }

    public function testHandleReturnsNotModifiedWhenStaticEtagMatches(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"abc123"',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertSame(ResponseCode::NOT_MODIFIED, $response->responseCode);
        self::assertSame('"abc123"', $this->findHeader($response, 'ETag'));
    }

    public function testHandleAnnotatesResponseWithEtagOnCacheMiss(): void
    {
        $response = (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
        self::assertSame('"abc123"', $this->findHeader($response, 'ETag'));
    }

    public function testHandleResolvesClosureEtagWithoutParameters(): void
    {
        $response = (new Cached(
            etag: static function (): string {
                return 'computed-etag';
            },
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame('"computed-etag"', $this->findHeader($response, 'ETag'));
    }

    public function testHandleResolvesClosureEtagWithRequestParameter(): void
    {
        $response = (new Cached(
            etag: static function (RequestInterface $request): string {
                return 'method-' . $request->method->name;
            },
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame('"method-GET"', $this->findHeader($response, 'ETag'));
    }

    public function testHandleSkipsEtagAnnotationWhenClosureReturnsNull(): void
    {
        $response = (new Cached(
            etag: static function (): ?string {
                return null;
            },
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertNull($this->findHeader($response, 'ETag'));
    }

    public function testHandleReturnsNotModifiedWhenLastModifiedIsAtOrAfterIfModifiedSince(): void
    {
        $next = new RecordingMiddleware();
        $lastModified = new \DateTimeImmutable('2026-01-01 12:00:00', new \DateTimeZone('UTC'));

        $response = (new Cached(
            lastModified: $lastModified,
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'If-Modified-Since' => 'Sat, 03 Jan 2026 12:00:00 GMT',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertSame(ResponseCode::NOT_MODIFIED, $response->responseCode);
        self::assertSame('Thu, 01 Jan 2026 12:00:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleAnnotatesResponseWithLastModifiedOnCacheMiss(): void
    {
        $lastModified = new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC'));

        $response = (new Cached(
            lastModified: $lastModified,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(ResponseCode::OK, $response->responseCode);
        self::assertSame('Thu, 15 Jan 2026 10:30:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleResolvesClosureLastModified(): void
    {
        $response = (new Cached(
            lastModified: static function (): \DateTimeInterface {
                return new \DateTimeImmutable('2026-02-01 00:00:00', new \DateTimeZone('UTC'));
            },
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame('Sun, 01 Feb 2026 00:00:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleSkipsLastModifiedAnnotationWhenClosureReturnsNull(): void
    {
        $response = (new Cached(
            lastModified: static function (): ?\DateTimeInterface {
                return null;
            },
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertNull($this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleAnnotatesBothEtagAndLastModifiedOnCacheMiss(): void
    {
        $lastModified = new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC'));

        $response = (new Cached(
            etag: 'abc123',
            lastModified: $lastModified,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame('"abc123"', $this->findHeader($response, 'ETag'));
        self::assertSame('Thu, 15 Jan 2026 10:30:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleReturns304WithBothEtagAndLastModifiedWhenEtagMatches(): void
    {
        $lastModified = new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC'));

        $response = (new Cached(
            etag: 'abc123',
            lastModified: $lastModified,
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"abc123"',
                    ],
                ),
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame(ResponseCode::NOT_MODIFIED, $response->responseCode);
        self::assertSame('"abc123"', $this->findHeader($response, 'ETag'));
        self::assertSame('Thu, 15 Jan 2026 10:30:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandlePreservesExistingEtagOnControllerResponse(): void
    {
        $existing = (new Response())->withHeader(
            new Header('ETag', '"controller-set"'),
        );

        $response = (new Cached(
            etag: 'middleware-default',
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame('"controller-set"', $this->findHeader($response, 'ETag'));
    }

    public function testHandlePreservesExistingLastModifiedOnControllerResponse(): void
    {
        $existing = (new Response())->withHeader(
            new Header('Last-Modified', 'Wed, 31 Dec 2025 00:00:00 GMT'),
        );

        $response = (new Cached(
            lastModified: new \DateTimeImmutable('2026-01-15 10:30:00', new \DateTimeZone('UTC')),
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(response: $existing),
        );

        self::assertSame('Wed, 31 Dec 2025 00:00:00 GMT', $this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleWithBothValidatorsNullPassesThrough(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Cached())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull($this->findHeader($response, 'ETag'));
        self::assertNull($this->findHeader($response, 'Last-Modified'));
    }

    public function testHandleDispatchesControllerWhenNotMatching(): void
    {
        $next = new RecordingMiddleware();

        (new Cached(
            etag: 'abc123',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'If-None-Match' => '"different"',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
