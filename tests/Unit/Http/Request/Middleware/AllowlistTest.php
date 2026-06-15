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
use Tuxxedo\Http\Request\Middleware\Allowlist;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;

class AllowlistTest extends TestCase
{
    private function makeRequest(
        string $ipAddress = '127.0.0.1',
    ): Request {
        return new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            ipAddress: $ipAddress,
        );
    }

    public function testAllowsIpv4LiteralMatch(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '127.0.0.1',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '127.0.0.1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testAllowsIpv6LiteralMatch(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '::1',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '::1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testRejectsRequestIpThatDoesNotMatchAnyEntry(): void
    {
        $next = new RecordingMiddleware();
        $caught = null;

        try {
            (new Allowlist(
                entries: [
                    '10.0.0.1',
                ],
            ))->handle(
                request: $this->makeRequest(
                    ipAddress: '127.0.0.1',
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::FORBIDDEN, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testAllowsIpv4WithinCidr(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '10.0.0.0/8',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.5.5.5',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testRejectsIpv4OutsideCidr(): void
    {
        $next = new RecordingMiddleware();
        $caught = null;

        try {
            (new Allowlist(
                entries: [
                    '10.0.0.0/8',
                ],
            ))->handle(
                request: $this->makeRequest(
                    ipAddress: '11.0.0.1',
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::FORBIDDEN, $caught->responseCode);
    }

    public function testAllowsCidrWithNonByteAlignedPrefix(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '10.0.0.0/23',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '10.0.1.5',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testAllowsCidrAtMaxPrefix(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '127.0.0.1/32',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '127.0.0.1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testAllowsIpv6Cidr(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                '2001:db8::/32',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '2001:db8:abcd::1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testRejectsCrossFamilyMatch(): void
    {
        $next = new RecordingMiddleware();
        $caught = null;

        try {
            (new Allowlist(
                entries: [
                    '::1',
                ],
            ))->handle(
                request: $this->makeRequest(
                    ipAddress: '127.0.0.1',
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::FORBIDDEN, $caught->responseCode);
    }

    public function testRejectsRequestWithUnparsableIpAddress(): void
    {
        $next = new RecordingMiddleware();
        $caught = null;

        try {
            (new Allowlist(
                entries: [
                    '10.0.0.0/8',
                ],
            ))->handle(
                request: $this->makeRequest(
                    ipAddress: 'not-an-ip',
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::FORBIDDEN, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testAllowsResolvedHostname(): void
    {
        $next = new RecordingMiddleware();

        (new Allowlist(
            entries: [
                'localhost',
            ],
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '127.0.0.1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testThrowsOnUnresolvableHostname(): void
    {
        $caught = null;

        try {
            new Allowlist(
                entries: [
                    'nonexistent-tuxxedo-allowlist.invalid',
                ],
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $caught->responseCode);
    }

    public function testThrowsOnCidrWithInvalidIp(): void
    {
        $caught = null;

        try {
            new Allowlist(
                entries: [
                    'not-an-ip/24',
                ],
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $caught->responseCode);
    }

    public function testThrowsOnCidrWithNonNumericPrefix(): void
    {
        $caught = null;

        try {
            new Allowlist(
                entries: [
                    '10.0.0.0/abc',
                ],
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $caught->responseCode);
    }

    public function testThrowsOnCidrPrefixOutOfRange(): void
    {
        $caught = null;

        try {
            new Allowlist(
                entries: [
                    '10.0.0.0/64',
                ],
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $caught->responseCode);
    }
}
