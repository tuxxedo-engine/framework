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
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\NetException;

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

    public function testHandleCallsNextWhenIpIsAllowed(): void
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

    public function testHandleThrowsForbiddenWhenIpIsNotAllowed(): void
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

    public function testConstructorPropagatesNetExceptionOnInvalidEntry(): void
    {
        self::expectException(NetException::class);

        new Allowlist(
            entries: [
                'not-an-ip/24',
            ],
        );
    }

    public function testConstructorAcceptsPreBuiltIpRangeList(): void
    {
        $next = new RecordingMiddleware();
        $ranges = new IpRangeList(
            entries: [
                '127.0.0.1',
            ],
        );

        (new Allowlist(
            entries: $ranges,
        ))->handle(
            request: $this->makeRequest(
                ipAddress: '127.0.0.1',
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
