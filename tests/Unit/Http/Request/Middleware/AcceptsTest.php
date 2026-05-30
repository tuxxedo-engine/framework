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
use Tuxxedo\Http\Request\Middleware\Accepts;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;

class AcceptsTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private function makeRequest(
        array $headers = [],
    ): Request {
        return new Request(
            headers: new StubHeaderContext(
                headers: $headers,
            ),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );
    }

    public function testConstructorThrowsWhenNoMimeTypesProvided(): void
    {
        $this->expectException(HttpException::class);

        new Accepts();
    }

    public function testHandleCallsNextWhenNegotiationMatches(): void
    {
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Accept' => 'application/json',
            ],
        );

        (new Accepts('application/json'))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleMatchesFromMultipleSupportedMimes(): void
    {
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Accept' => 'application/json',
            ],
        );

        (new Accepts('text/html', 'application/json'))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandlePassesRequestToNext(): void
    {
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Accept' => 'application/json',
            ],
        );

        (new Accepts('application/json'))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame($request, $next->lastRequest);
    }

    public function testHandleFallsBackToFirstSupportedWhenAcceptHeaderIsMissing(): void
    {
        $next = new RecordingMiddleware();
        $request = $this->makeRequest();

        (new Accepts('text/html'))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleThrowsNotAcceptableWhenNoSupportedTypeMatches(): void
    {
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Accept' => 'application/xml',
            ],
        );

        $caught = null;

        try {
            (new Accepts('application/json'))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::NOT_ACCEPTABLE, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }
}
