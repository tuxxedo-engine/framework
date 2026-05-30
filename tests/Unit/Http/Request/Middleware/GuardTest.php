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
use Tuxxedo\Http\Request\Middleware\Guard;
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class GuardTest extends TestCase
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

    public function testHandleInvokesClosureWithRequestAndNext(): void
    {
        $request = $this->makeRequest();
        $next = new RecordingMiddleware();

        $receivedRequest = null;
        $receivedNext = null;

        $guard = new Guard(
            guard: static function (
                RequestInterface $request,
                MiddlewareInterface $next,
            ) use (&$receivedRequest, &$receivedNext): ResponseInterface {
                $receivedRequest = $request;
                $receivedNext = $next;

                return new Response();
            },
        );

        $guard->handle(
            request: $request,
            next: $next,
        );

        self::assertSame($request, $receivedRequest);
        self::assertSame($next, $receivedNext);
    }

    public function testHandleReturnsClosureResult(): void
    {
        $response = new Response();
        $guard = new Guard(
            guard: static fn (
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface => $response,
        );

        $result = $guard->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );

        self::assertSame($response, $result);
    }

    public function testHandleDoesNotInvokeNextItself(): void
    {
        $next = new RecordingMiddleware();
        $guard = new Guard(
            guard: static fn (
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface => new Response(),
        );

        $guard->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(0, $next->callCount);
    }
}
