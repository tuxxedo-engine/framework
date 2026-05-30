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
use Tuxxedo\Http\Request\Middleware\StrictTransportSecurity;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class StrictTransportSecurityTest extends TestCase
{
    private function makeRequest(
        bool $https,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            https: $https,
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

    public function testHandleAddsStrictTransportSecurityHeaderOverHttps(): void
    {
        $next = new RecordingMiddleware();

        $response = (new StrictTransportSecurity())->handle(
            request: $this->makeRequest(
                https: true,
            ),
            next: $next,
        );

        self::assertSame(
            'max-age=31536000; includeSubDomains; preload',
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }

    public function testHandleDoesNotAddHeaderOverHttp(): void
    {
        $next = new RecordingMiddleware();

        $response = (new StrictTransportSecurity())->handle(
            request: $this->makeRequest(
                https: false,
            ),
            next: $next,
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }

    public function testHandleAlwaysCallsNext(): void
    {
        $next = new RecordingMiddleware();

        (new StrictTransportSecurity())->handle(
            request: $this->makeRequest(
                https: false,
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleUsesCustomMaxAge(): void
    {
        $next = new RecordingMiddleware(
            response: new Response(),
        );

        $response = (new StrictTransportSecurity(
            maxAge: 600,
        ))->handle(
            request: $this->makeRequest(
                https: true,
            ),
            next: $next,
        );

        self::assertSame(
            'max-age=600; includeSubDomains; preload',
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }

    public function testHandleOmitsIncludeSubDomainsWhenDisabled(): void
    {
        $next = new RecordingMiddleware();

        $response = (new StrictTransportSecurity(
            includeSubDomains: false,
        ))->handle(
            request: $this->makeRequest(
                https: true,
            ),
            next: $next,
        );

        self::assertSame(
            'max-age=31536000; preload',
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }

    public function testHandleOmitsPreloadWhenDisabled(): void
    {
        $next = new RecordingMiddleware();

        $response = (new StrictTransportSecurity(
            preload: false,
        ))->handle(
            request: $this->makeRequest(
                https: true,
            ),
            next: $next,
        );

        self::assertSame(
            'max-age=31536000; includeSubDomains',
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }

    public function testHandleEmitsBareMaxAgeWhenAllOptionalDirectivesDisabled(): void
    {
        $next = new RecordingMiddleware();

        $response = (new StrictTransportSecurity(
            includeSubDomains: false,
            preload: false,
        ))->handle(
            request: $this->makeRequest(
                https: true,
            ),
            next: $next,
        );

        self::assertSame(
            'max-age=31536000',
            $this->findHeader(
                response: $response,
                name: 'Strict-Transport-Security',
            ),
        );
    }
}
