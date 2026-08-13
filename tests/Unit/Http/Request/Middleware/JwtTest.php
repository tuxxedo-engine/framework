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
use Support\Security\Jwt\JwtKeyFixtures;
use Support\Temporal\FixedClock;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\Jwt;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\Constraint\ValidAt;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\JwtTokenAccessor;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class JwtTest extends TestCase
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
            method: Method::GET,
        );
    }

    private function key(): SymmetricKey
    {
        return new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
    }

    private function otherKey(): SymmetricKey
    {
        return new SymmetricKey(
            secret: \str_repeat('x', 64),
        );
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function issue(
        JwtManager $manager,
        array $claims = [],
        ?SymmetricKey $key = null,
    ): string {
        return $manager->encode(
            claims: $claims,
            algorithm: Algorithm::HS256,
            key: $key ?? $this->key(),
        )->compact;
    }

    public function testHandleDecodesValidBearerTokenAndPopulatesAccessor(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();

        $compact = $this->issue(
            manager: $manager,
            claims: [
                'sub' => 'user-1',
            ],
        );

        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer ' . $compact,
            ],
        );

        (new Jwt(
            manager: $manager,
            accessor: $accessor,
            constraints: [
                new SignedWith(
                    algorithm: Algorithm::HS256,
                    key: $this->key(),
                ),
            ],
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);

        $token = $accessor->current();

        self::assertNotNull($token);
        self::assertSame('user-1', $token->claims->subject);
    }

    public function testHandleThrowsUnauthorizedWhenAuthorizationHeaderMissing(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest();

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertSame(0, $next->callCount);
        self::assertNull($accessor->current());
    }

    public function testHandleThrowsUnauthorizedWhenSchemeIsNotBearer(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Basic dXNlcjpwYXNz',
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testHandleThrowsUnauthorizedWhenBearerValueIsEmpty(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer ',
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testHandleThrowsUnauthorizedForMalformedCompactString(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer not-a-jwt',
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertInstanceOf(JwtException::class, $caught->getPrevious());
        self::assertSame(0, $next->callCount);
    }

    public function testHandleThrowsUnauthorizedWhenSignatureFailsToVerify(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();

        $compact = $this->issue(
            manager: $manager,
            claims: [
                'sub' => 'user-1',
            ],
            key: $this->otherKey(),
        );

        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer ' . $compact,
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
                constraints: [
                    new SignedWith(
                        algorithm: Algorithm::HS256,
                        key: $this->key(),
                    ),
                ],
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertInstanceOf(JwtException::class, $caught->getPrevious());
        self::assertSame(0, $next->callCount);
        self::assertNull($accessor->current());
    }

    public function testHandleThrowsUnauthorizedWhenTokenIsExpired(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $clock = new FixedClock(
            now: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );

        $compact = $this->issue(
            manager: $manager,
            claims: [
                'sub' => 'user-1',
                'exp' => $clock->now()->getTimestamp() - 60,
            ],
        );

        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer ' . $compact,
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
                constraints: [
                    new SignedWith(
                        algorithm: Algorithm::HS256,
                        key: $this->key(),
                    ),
                    new ValidAt(
                        clock: $clock,
                    ),
                ],
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertInstanceOf(JwtException::class, $caught->getPrevious());
        self::assertSame(0, $next->callCount);
    }

    public function testHandlePassesThroughWhenNotRequiredAndHeaderMissing(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest();

        (new Jwt(
            manager: $manager,
            accessor: $accessor,
            constraints: [],
            required: false,
        ))->handle(
            request: $request,
            next: $next,
        );

        self::assertSame(1, $next->callCount);
        self::assertNull($accessor->current());
    }

    public function testHandleStillThrowsUnauthorizedWhenNotRequiredButTokenInvalid(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer not-a-jwt',
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
                constraints: [],
                required: false,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertSame(ResponseCode::UNAUTHORIZED, $caught->responseCode);
        self::assertSame(0, $next->callCount);
    }

    public function testHandleUnauthorizedPreservesJwtExceptionAsPrevious(): void
    {
        $manager = new JwtManager();
        $accessor = new JwtTokenAccessor();
        $next = new RecordingMiddleware();
        $request = $this->makeRequest(
            headers: [
                'Authorization' => 'Bearer aaa.bbb.ccc',
            ],
        );

        $caught = null;

        try {
            (new Jwt(
                manager: $manager,
                accessor: $accessor,
            ))->handle(
                request: $request,
                next: $next,
            );
        } catch (HttpException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpException::class, $caught);
        self::assertInstanceOf(JwtException::class, $caught->getPrevious());
    }
}
