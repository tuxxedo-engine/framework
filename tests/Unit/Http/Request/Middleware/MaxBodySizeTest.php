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
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;
use Tuxxedo\Http\Request\Middleware\MaxBodySize;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;

class MaxBodySizeTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'max_body_size_test_');
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            \unlink($this->tempFile);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function makeRequest(
        array $headers = [],
        ?BodyContextInterface $body = null,
    ): Request {
        return new Request(
            headers: new StubHeaderContext(
                headers: $headers,
            ),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: $body ?? new StubBodyContext(),
        );
    }

    private function makeBodyContextWithContent(
        string $content,
    ): EnvironmentBodyContext {
        \file_put_contents($this->tempFile, $content);

        return new EnvironmentBodyContext(
            streamInputSource: $this->tempFile,
        );
    }

    public function testHandleCallsNextWhenContentLengthHeaderIsMissing(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
        ))->handle(
            request: $this->makeRequest(),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleCallsNextWhenContentLengthIsBelowLimit(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '512',
                ],
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleCallsNextWhenContentLengthEqualsLimit(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '1024',
                ],
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleCallsNextWhenContentLengthIsZero(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '0',
                ],
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleThrowsPayloadTooLargeWhenContentLengthExceedsLimit(): void
    {
        $caught = null;
        $next = new RecordingMiddleware();

        try {
            (new MaxBodySize(
                maxBytes: 1024,
            ))->handle(
                request: $this->makeRequest(
                    headers: [
                        'Content-Length' => '2048',
                    ],
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::PAYLOAD_TOO_LARGE, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testHandleThrowsForContentLengthOneByteOverLimit(): void
    {
        $this->expectException(HttpException::class);

        (new MaxBodySize(
            maxBytes: 1024,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '1025',
                ],
            ),
            next: new RecordingMiddleware(),
        );
    }

    public function testStrictModeCallsNextWhenBodyMatchesContentLengthAndWithinLimit(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
            verifyActualSize: true,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '5',
                ],
                body: $this->makeBodyContextWithContent('hello'),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testStrictModeCallsNextWhenBodyEqualsLimitExactly(): void
    {
        $payload = \str_repeat('x', 1024);
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
            verifyActualSize: true,
        ))->handle(
            request: $this->makeRequest(
                headers: [
                    'Content-Length' => '1024',
                ],
                body: $this->makeBodyContextWithContent($payload),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testStrictModeThrowsWhenActualBodyExceedsLimitDespiteSpoofedContentLength(): void
    {
        $payload = \str_repeat('x', 2048);
        $next = new RecordingMiddleware();

        $caught = null;

        try {
            (new MaxBodySize(
                maxBytes: 1024,
                verifyActualSize: true,
            ))->handle(
                request: $this->makeRequest(
                    headers: [
                        'Content-Length' => '100',
                    ],
                    body: $this->makeBodyContextWithContent($payload),
                ),
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::PAYLOAD_TOO_LARGE, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testStrictModeThrowsWhenBodyExceedsLimitWithoutContentLengthHeader(): void
    {
        $payload = \str_repeat('x', 2048);
        $next = new RecordingMiddleware();

        $this->expectException(HttpException::class);

        try {
            (new MaxBodySize(
                maxBytes: 1024,
                verifyActualSize: true,
            ))->handle(
                request: $this->makeRequest(
                    body: $this->makeBodyContextWithContent($payload),
                ),
                next: $next,
            );
        } finally {
            self::assertSame(0, $next->callCount);
        }
    }

    public function testStrictModeThrowsForBodyOneByteOverLimit(): void
    {
        $payload = \str_repeat('x', 1025);

        $this->expectException(HttpException::class);

        (new MaxBodySize(
            maxBytes: 1024,
            verifyActualSize: true,
        ))->handle(
            request: $this->makeRequest(
                body: $this->makeBodyContextWithContent($payload),
            ),
            next: new RecordingMiddleware(),
        );
    }

    public function testStrictModeCallsNextForEmptyBody(): void
    {
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 1024,
            verifyActualSize: true,
        ))->handle(
            request: $this->makeRequest(
                body: $this->makeBodyContextWithContent(''),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testStrictModeReadsBodyAcrossMultipleChunks(): void
    {
        $payload = \str_repeat('x', 16384);
        $next = new RecordingMiddleware();

        (new MaxBodySize(
            maxBytes: 32768,
            verifyActualSize: true,
        ))->handle(
            request: $this->makeRequest(
                body: $this->makeBodyContextWithContent($payload),
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }
}
