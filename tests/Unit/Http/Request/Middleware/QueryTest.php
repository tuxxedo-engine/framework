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
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\Query;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class QueryTest extends TestCase
{
    private function makeRequest(
        Method $method = Method::QUERY,
        ?StubHeaderContext $headers = null,
    ): Request {
        return new Request(
            headers: $headers ?? new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: $method,
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

    public function testHandlePassesThroughForNonQueryMethod(): void
    {
        $next = new RecordingMiddleware();

        (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(
                method: Method::GET,
            ),
            next: $next,
        );

        self::assertSame(1, $next->callCount);
    }

    public function testHandleThrowsBadRequestWhenContentTypeIsMissing(): void
    {
        $this->expectException(HttpException::class);

        (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(),
            next: new RecordingMiddleware(),
        );
    }

    public function testHandleReturnsUnsupportedMediaTypeForUnacceptedContentType(): void
    {
        $next = new RecordingMiddleware();

        $response = (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'text/plain',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            0,
            $next->callCount,
        );
        self::assertSame(
            ResponseCode::UNSUPPORTED_MEDIA_TYPE,
            $response->responseCode,
        );
    }

    public function testUnsupportedMediaTypeResponseCarriesAcceptQueryHint(): void
    {
        $response = (new Query(
            'application/json',
            'application/sql',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'text/plain',
                    ],
                ),
            ),
            next: new RecordingMiddleware(),
        );

        self::assertSame(
            '"application/json", "application/sql"',
            $this->findHeader($response, 'Accept-Query'),
        );
    }

    public function testHandlePassesThroughForAcceptedContentType(): void
    {
        $next = new RecordingMiddleware();

        (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'application/json',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            1,
            $next->callCount,
        );
    }

    public function testHandleIgnoresContentTypeParametersWhenMatching(): void
    {
        $next = new RecordingMiddleware();

        (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'application/json; charset=UTF-8',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            1,
            $next->callCount,
        );
    }

    public function testHandleIsCaseInsensitiveOnMediaTypeComparison(): void
    {
        $next = new RecordingMiddleware();

        (new Query(
            'application/json',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'Application/JSON',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            1,
            $next->callCount,
        );
    }

    public function testHandleNormalizesAcceptedMediaTypesToLowercase(): void
    {
        $next = new RecordingMiddleware();

        (new Query(
            'APPLICATION/JSON',
        ))->handle(
            request: $this->makeRequest(
                headers: new StubHeaderContext(
                    [
                        'Content-Type' => 'application/json',
                    ],
                ),
            ),
            next: $next,
        );

        self::assertSame(
            1,
            $next->callCount,
        );
    }
}
