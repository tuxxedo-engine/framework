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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Http\Request\Middleware\RecordingMiddleware;
use Support\Security\Csrf\StubCsrfManager;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Middleware\Csrf;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;

class CsrfTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private function makeRequest(
        Method $method,
        InputContextInterface $post = new StubInputContext(),
        array $headers = [],
    ): Request {
        return new Request(
            headers: new StubHeaderContext(
                headers: $headers,
            ),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: $post,
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: $method,
        );
    }

    /**
     * @param array<string, string> $values
     */
    private function postWith(
        array $values,
    ): InputContextInterface {
        return new class ($values) extends StubInputContext {
            /**
             * @param array<string, string> $values
             */
            public function __construct(
                private readonly array $values,
            ) {
            }

            public function has(
                string $name,
            ): bool {
                return \array_key_exists($name, $this->values);
            }

            public function string(
                string $name,
                string $default = '',
            ): string {
                return $this->values[$name] ?? $default;
            }
        };
    }

    /**
     * @return \Generator<array{0: Method}>
     */
    public static function safeMethodProvider(): \Generator
    {
        yield [
            Method::GET,
        ];

        yield [
            Method::HEAD,
        ];

        yield [
            Method::OPTIONS,
        ];
    }

    /**
     * @return \Generator<array{0: Method}>
     */
    public static function unsafeMethodProvider(): \Generator
    {
        yield [
            Method::POST,
        ];

        yield [
            Method::PUT,
        ];

        yield [
            Method::PATCH,
        ];

        yield [
            Method::DELETE,
        ];
    }

    #[DataProvider('safeMethodProvider')]
    public function testHandleSkipsValidationForSafeMethods(
        Method $method,
    ): void {
        $csrf = new StubCsrfManager(
            validResult: false,
        );

        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: $method,
        );

        (new Csrf(
            csrf: $csrf,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull($csrf->lastValidatedToken);
    }

    #[DataProvider('unsafeMethodProvider')]
    public function testHandleValidatesTokenFromPostForUnsafeMethods(
        Method $method,
    ): void {
        $csrf = new StubCsrfManager();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: $method,
            post: $this->postWith(
                values: [
                    'csrf_token' => 'valid-token',
                ],
            ),
        );

        (new Csrf(
            csrf: $csrf,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame('valid-token', $csrf->lastValidatedToken);
        self::assertSame(1, $next->callCount);
    }

    public function testHandleValidatesTokenFromHeaderWhenPostMissing(): void
    {
        $csrf = new StubCsrfManager();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: Method::POST,
            headers: [
                'X-Csrf-Token' => 'header-token',
            ],
        );

        (new Csrf(
            csrf: $csrf,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame('header-token', $csrf->lastValidatedToken);
    }

    public function testHandlePrefersPostTokenOverHeaderToken(): void
    {
        $csrf = new StubCsrfManager();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: Method::POST,
            post: $this->postWith(
                values: [
                    'csrf_token' => 'post-token',
                ],
            ),
            headers: [
                'X-Csrf-Token' => 'header-token',
            ],
        );

        (new Csrf(
            csrf: $csrf,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame('post-token', $csrf->lastValidatedToken);
    }

    public function testHandleValidatesEmptyStringWhenNoTokenProvided(): void
    {
        $csrf = new StubCsrfManager();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: Method::POST,
        );

        (new Csrf(
            csrf: $csrf,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame('', $csrf->lastValidatedToken);
    }

    public function testHandleThrowsForbiddenWhenValidationFails(): void
    {
        $csrf = new StubCsrfManager(
            validResult: false,
        );

        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            method: Method::POST,
            post: $this->postWith(
                values: [
                    'csrf_token' => 'bad-token',
                ],
            ),
        );

        $caught = null;

        try {
            (new Csrf(
                csrf: $csrf,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::FORBIDDEN, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }
}
