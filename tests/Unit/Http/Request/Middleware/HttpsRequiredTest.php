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
use Tuxxedo\Http\Request\Middleware\HttpsRequired;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class HttpsRequiredTest extends TestCase
{
    private function makeRequest(
        ?string $uri = null,
        bool $https = true,
        ?string $host = null,
        ?int $port = null,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            uri: $uri,
            https: $https,
            host: $host,
            port: $port,
        );
    }

    private function findLocationHeader(
        ResponseInterface $response,
    ): ?string {
        foreach ($response->headers as $header) {
            if (\strcasecmp($header->name, 'Location') === 0) {
                return $header->value;
            }
        }

        return null;
    }

    public function testHandleCallsNextWhenRequestIsHttps(): void
    {
        $next = new RecordingMiddleware();

        (new HttpsRequired())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleRedirectsToHttpsWhenRequestIsNotHttps(): void
    {
        $next = new RecordingMiddleware();

        $response = (new HttpsRequired())->handle(
            request: $this->makeRequest(
                uri: '/foo?bar=baz',
                https: false,
                host: 'example.com',
                port: 80,
            ),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
        self::assertSame('https://example.com/foo?bar=baz', $this->findLocationHeader($response));
    }

    public function testHandleUsesMovedPermanentlyByDefault(): void
    {
        $response = (new HttpsRequired())->handle(
            request: $this->makeRequest(
                https: false,
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame(ResponseCode::MOVED_PERMANENTLY, $response->responseCode);
    }

    public function testHandlePropagatesCustomResponseCode(): void
    {
        $response = (new HttpsRequired(
            responseCode: ResponseCode::TEMPORARY_REDIRECT,
        ))->handle(
            request: $this->makeRequest(
                https: false,
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame(ResponseCode::TEMPORARY_REDIRECT, $response->responseCode);
    }

    public function testHandleOmitsPortFromRedirectWhenPortIs80(): void
    {
        $response = (new HttpsRequired())->handle(
            request: $this->makeRequest(
                uri: '/',
                https: false,
                host: 'example.com',
                port: 80,
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame('https://example.com/', $this->findLocationHeader($response));
    }

    public function testHandleOmitsPortFromRedirectWhenPortIs443(): void
    {
        $response = (new HttpsRequired())->handle(
            request: $this->makeRequest(
                uri: '/',
                https: false,
                host: 'example.com',
                port: 443,
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame('https://example.com/', $this->findLocationHeader($response));
    }

    public function testHandleIncludesNonStandardPortInRedirect(): void
    {
        $response = (new HttpsRequired())->handle(
            request: $this->makeRequest(
                uri: '/',
                https: false,
                host: 'example.com',
                port: 8080,
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame('https://example.com:8080/', $this->findLocationHeader($response));
    }
}
