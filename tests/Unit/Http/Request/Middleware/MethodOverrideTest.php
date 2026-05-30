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
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\MethodOverride;
use Tuxxedo\Http\Request\Request;

class MethodOverrideTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string> $post
     */
    private function makeRequest(
        Method $method = Method::POST,
        array $headers = [],
        array $post = [],
    ): Request {
        return new Request(
            headers: new StubHeaderContext(
                headers: $headers,
            ),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(
                values: $post,
            ),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: $method,
        );
    }

    public function testHandlePassesThroughForGetMethod(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                method: Method::GET,
                post: [
                    '_method' => 'PUT',
                ],
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::GET, $next->lastRequest->method);
    }

    public function testHandlePassesThroughForPostWithoutOverride(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::POST, $next->lastRequest->method);
    }

    public function testHandleAppliesOverrideFromPostBody(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => 'PUT',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::PUT, $next->lastRequest->method);
    }

    public function testHandleAppliesOverrideFromHeader(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                headers: [
                    'X-HTTP-Method-Override' => 'DELETE',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::DELETE, $next->lastRequest->method);
    }

    public function testHandlePrefersPostBodyOverHeader(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                headers: [
                    'X-HTTP-Method-Override' => 'DELETE',
                ],
                post: [
                    '_method' => 'PUT',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::PUT, $next->lastRequest->method);
    }

    public function testHandleAcceptsLowercaseOverride(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => 'put',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::PUT, $next->lastRequest->method);
    }

    public function testHandleTrimsWhitespaceFromOverride(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => '  PATCH  ',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::PATCH, $next->lastRequest->method);
    }

    public function testHandleIgnoresUnknownMethodOverride(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => 'BOGUS',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::POST, $next->lastRequest->method);
    }

    public function testHandleIgnoresEmptyOverrideValue(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => '',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::POST, $next->lastRequest->method);
    }

    public function testHandleIgnoresWhitespaceOnlyOverrideValue(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => '   ',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::POST, $next->lastRequest->method);
    }

    public function testHandleSupportsCustomFieldName(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride(
            field: '__http_method',
        ))->handle(
            request: $this->makeRequest(
                post: [
                    '__http_method' => 'DELETE',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::DELETE, $next->lastRequest->method);
    }

    public function testHandleSupportsCustomHeaderName(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride(
            header: 'X-Method-Override',
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'X-Method-Override' => 'PUT',
                ],
            ),
            next: $next,
        );

        self::assertNotNull($next->lastRequest);
        self::assertSame(Method::PUT, $next->lastRequest->method);
    }

    public function testHandleCallsNext(): void
    {
        $next = new RecordingMiddleware();

        (new MethodOverride())->handle(
            request: $this->makeRequest(
                post: [
                    '_method' => 'PUT',
                ],
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
