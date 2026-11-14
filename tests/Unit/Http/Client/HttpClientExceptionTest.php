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

namespace Unit\Http\Client;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Response\ClientResponse;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Validator\ValidationException;

class HttpClientExceptionTest extends TestCase
{
    private static function request(): ClientRequest
    {
        return new ClientRequest(Method::GET, 'https://user:secret@api.example:8443/users/1?api_key=secret');
    }

    /**
     * @return \Generator<array{0: \Closure(): HttpClientException, 1: HttpClientFailure, 2: ResponseCode, 3: string}>
     */
    public static function factoryDataProvider(): \Generator
    {
        $response = new ClientResponse(statusCode: 404);

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnexpectedStatus(self::request(), $response),
            HttpClientFailure::STATUS,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 responded with 404',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromEmptyBody(self::request(), new ClientResponse(statusCode: 204)),
            HttpClientFailure::BODY,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 responded with 204 and an empty body',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromMalformedBody(self::request(), $response, new \RuntimeException('Syntax error')),
            HttpClientFailure::BODY,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned a malformed body: Syntax error',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromInvalidBody(self::request(), $response, new ValidationException([])),
            HttpClientFailure::BODY,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned a body that does not match the expected shape',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnsupportedContentType(
                self::request(),
                new ClientResponse(
                    headers: [
                        new Header('Content-Type', 'text/yaml'),
                    ],
                ),
            ),
            HttpClientFailure::BODY,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned the unsupported content type "text/yaml"',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnsupportedContentType(self::request(), $response),
            HttpClientFailure::BODY,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned the unsupported content type ""',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromTransportError(self::request(), 6, 'Could not resolve host'),
            HttpClientFailure::TRANSPORT,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 failed: Could not resolve host (curl error 6)',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromTimeout(self::request(), HttpClientTimeout::IDLE),
            HttpClientFailure::TIMEOUT,
            ResponseCode::GATEWAY_TIMEOUT,
            'GET https://api.example:8443/users/1 hit the idle timeout',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromMissingStatusLine(self::request()),
            HttpClientFailure::PROTOCOL,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned no status line',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromMalformedResponseHead(self::request()),
            HttpClientFailure::PROTOCOL,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned a status line or header that could not be parsed',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromMissingTransportResult(self::request()),
            HttpClientFailure::PROTOCOL,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 got no result from the transport',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromPrivateNetwork(self::request(), '10.0.0.1'),
            HttpClientFailure::RULE,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 was denied because "10.0.0.1" is a private network address',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnmatchedRequest(self::request()),
            HttpClientFailure::RULE,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 matched no rule and unmatched requests are denied',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromTooManyRedirects(self::request(), 5),
            HttpClientFailure::REDIRECT,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 redirected more than 5 times',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromResponseTooLarge(self::request(), 1024),
            HttpClientFailure::SIZE,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 returned a response larger than 1024 bytes',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromDeniedByRule(self::request(), 'deny *.internal'),
            HttpClientFailure::RULE,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 was denied by the rule "deny *.internal"',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnrewindableBody(self::request()),
            HttpClientFailure::REPLAY,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 needs its body sent again, but the body is a stream that can only be read once',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromDotSegments(self::request()),
            HttpClientFailure::REQUEST,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 contains dot segments, which are not resolved against the base URL',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromRateLimited(self::request()),
            HttpClientFailure::RATE_LIMITED,
            ResponseCode::SERVICE_UNAVAILABLE,
            'GET https://api.example:8443/users/1 was refused because the client rate limit is exhausted',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromAborted(self::request()),
            HttpClientFailure::ABORTED,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 was aborted by its progress callback',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromForeignOrigin(self::request()),
            HttpClientFailure::REQUEST,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 is outside the base URL of a strict client',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromUnwritableDownload(self::request(), '/tmp/file'),
            HttpClientFailure::REQUEST,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 could not be downloaded to "/tmp/file"',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromExhaustedSequence(self::request(), 'api.example'),
            HttpClientFailure::RULE,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 matched the rule "api.example", but its response sequence is exhausted',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromMissingToken(self::request()),
            HttpClientFailure::AUTH,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'GET https://api.example:8443/users/1 needs an access token, but the token store has no usable token',
        ];

        yield [
            static fn (): HttpClientException => HttpClientException::fromTokenRequestFailed(self::request(), new ClientResponse(statusCode: 401)),
            HttpClientFailure::AUTH,
            ResponseCode::BAD_GATEWAY,
            'GET https://api.example:8443/users/1 did not return a usable access token (status 401)',
        ];
    }

    /**
     * @param \Closure(): HttpClientException $factory
     */
    #[DataProvider('factoryDataProvider')]
    public function testFactories(
        \Closure $factory,
        HttpClientFailure $failure,
        ResponseCode $responseCode,
        string $message,
    ): void {
        $exception = $factory();

        self::assertSame($failure, $exception->failure);
        self::assertSame($responseCode, $exception->responseCode);
        self::assertSame($message, $exception->getMessage());
    }

    public function testRequestResponseAndPreviousAreKept(): void
    {
        $request = self::request();
        $response = new ClientResponse(statusCode: 500);
        $previous = new \RuntimeException('cause');
        $exception = HttpClientException::fromMalformedBody($request, $response, $previous);

        self::assertSame($request, $exception->request);
        self::assertSame($response, $exception->response);
        self::assertSame($previous, $exception->getPrevious());
        self::assertNull(HttpClientException::fromMissingToken($request)->response);
    }

    public function testRelativeUrlsShowOnlyThePath(): void
    {
        self::assertSame(
            'POST /users responded with 500',
            HttpClientException::fromUnexpectedStatus(
                new ClientRequest(Method::POST, '/users?token=secret'),
                new ClientResponse(statusCode: 500),
            )->getMessage(),
        );
    }
}
