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
use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;
use Tuxxedo\Http\Request\Middleware\OutputCapture;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class OutputCaptureTest extends TestCase
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

    /**
     * @param \Closure(): void $emit
     */
    private function emittingMiddleware(
        \Closure $emit,
        ResponseInterface $response = new Response(),
    ): MiddlewareInterface {
        return new class ($emit, $response) implements MiddlewareInterface {
            /**
             * @param \Closure(): void $emit
             */
            public function __construct(
                private readonly \Closure $emit,
                private readonly ResponseInterface $response,
            ) {
            }

            public function handle(
                RequestInterface $request,
                MiddlewareInterface $next,
            ): ResponseInterface {
                ($this->emit)();

                return $this->response;
            }
        };
    }

    public function testHandleCapturesEchoedOutputIntoResponseBody(): void
    {
        $next = $this->emittingMiddleware(
            emit: static function (): void {
                echo 'captured-output';
            },
        );

        $response = (new OutputCapture())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame('captured-output', $response->body);
    }

    public function testHandleReplacesExistingResponseBody(): void
    {
        $next = $this->emittingMiddleware(
            emit: static function (): void {
                echo 'new-body';
            },
            response: new Response(
                body: 'original-body',
            ),
        );

        $response = (new OutputCapture())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame('new-body', $response->body);
    }

    public function testHandleSetsEmptyBodyWhenNothingIsEmitted(): void
    {
        $next = $this->emittingMiddleware(
            emit: static function (): void {
            },
        );

        $response = (new OutputCapture())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame('', $response->body);
    }

    public function testHandlePreservesResponseHeadersAndCode(): void
    {
        $original = Response::redirect(
            uri: 'https://example.com',
        );

        $next = $this->emittingMiddleware(
            emit: static function (): void {
                echo 'noise';
            },
            response: $original,
        );

        $response = (new OutputCapture())->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame($original->responseCode, $response->responseCode);
        self::assertSame($original->headers, $response->headers);
    }
}
