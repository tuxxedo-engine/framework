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
use Tuxxedo\Http\Request\Middleware\XssProtection;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseInterface;

class XssProtectionTest extends TestCase
{
    private function makeRequest(): Request
    {
        return new Request(
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

    public function testHandleAddsDefaultContentSecurityPolicyHeader(): void
    {
        $response = (new XssProtection())->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'default-src \'self\'',
            $this->findHeader(
                response: $response,
                name: 'Content-Security-Policy',
            ),
        );
    }

    public function testHandleJoinsMultipleContentSecurityPolicyDirectives(): void
    {
        $response = (new XssProtection(
            contentSecurityPolicies: [
                'default-src' => '\'self\'',
                'img-src' => '\'self\' data:',
            ],
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'default-src \'self\'; img-src \'self\' data:',
            $this->findHeader(
                response: $response,
                name: 'Content-Security-Policy',
            ),
        );
    }

    public function testHandleOmitsContentSecurityPolicyHeaderWhenPoliciesEmpty(): void
    {
        $response = (new XssProtection(
            contentSecurityPolicies: [],
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'Content-Security-Policy',
            ),
        );
    }

    public function testHandleAddsXssProtectionWithBlockModeByDefault(): void
    {
        $response = (new XssProtection())->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            '1; mode=block',
            $this->findHeader(
                response: $response,
                name: 'X-XSS-Protection',
            ),
        );
    }

    public function testHandleAddsXssProtectionWithReportUriWhenSet(): void
    {
        $response = (new XssProtection(
            xssProtectionReportUri: 'https://example.com/csp-report',
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            '1; report=https://example.com/csp-report',
            $this->findHeader(
                response: $response,
                name: 'X-XSS-Protection',
            ),
        );
    }

    public function testHandleEmitsBareXssProtectionWhenBlockIsDisabledAndNoReportUri(): void
    {
        $response = (new XssProtection(
            xssProtectionBlock: false,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            '1',
            $this->findHeader(
                response: $response,
                name: 'X-XSS-Protection',
            ),
        );
    }

    public function testHandleEmitsDisabledXssProtectionWhenEnabledIsFalse(): void
    {
        $response = (new XssProtection(
            xssProtectionEnabled: false,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            '0',
            $this->findHeader(
                response: $response,
                name: 'X-XSS-Protection',
            ),
        );
    }

    public function testHandleAddsContentTypeOptionsNoSniffByDefault(): void
    {
        $response = (new XssProtection())->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            'nosniff',
            $this->findHeader(
                response: $response,
                name: 'X-Content-Type-Options',
            ),
        );
    }

    public function testHandleOmitsContentTypeOptionsWhenDisabled(): void
    {
        $response = (new XssProtection(
            contentTypeOptionsNoSniff: false,
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertNull(
            $this->findHeader(
                response: $response,
                name: 'X-Content-Type-Options',
            ),
        );
    }

    public function testHandleCallsNext(): void
    {
        $next = new RecordingMiddleware();

        (new XssProtection())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
