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

namespace Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class HttpExceptionTest extends TestCase
{
    public function testConstructorWithResponseCode(): void
    {
        $exception = new HttpException(ResponseCode::NOT_FOUND);

        self::assertSame(ResponseCode::NOT_FOUND, $exception->responseCode);
        self::assertSame('Not Found', $exception->getMessage());
    }

    public function testConstructorWithInt(): void
    {
        $exception = new HttpException(404);

        self::assertSame(ResponseCode::NOT_FOUND, $exception->responseCode);
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('original');
        $exception = new HttpException(ResponseCode::INTERNAL_SERVER_ERROR, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testToResponse(): void
    {
        $exception = new HttpException(ResponseCode::NOT_FOUND);
        $response = $exception->toResponse();

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(ResponseCode::NOT_FOUND, $response->responseCode);
    }

    /**
     * @return \Generator<array{0: ResponseCode, 1: \Closure(): HttpException}>
     */
    public static function fromFactoryDataProvider(): \Generator
    {
        yield [
            ResponseCode::CONTINUE,
            HttpException::fromContinue(...),
        ];

        yield [
            ResponseCode::SWITCHING_PROTOCOLS,
            HttpException::fromSwitchingProtocols(...),
        ];

        yield [
            ResponseCode::PROCESSING,
            HttpException::fromProcessing(...),
        ];

        yield [
            ResponseCode::EARLY_HINTS,
            HttpException::fromEarlyHints(...),
        ];

        yield [
            ResponseCode::OK,
            HttpException::fromOk(...),
        ];

        yield [
            ResponseCode::CREATED,
            HttpException::fromCreated(...),
        ];

        yield [
            ResponseCode::ACCEPTED,
            HttpException::fromAccepted(...),
        ];

        yield [
            ResponseCode::NON_AUTHORITATIVE_INFORMATION,
            HttpException::fromNonAuthoritativeInformation(...),
        ];

        yield [
            ResponseCode::NO_CONTENT,
            HttpException::fromNoContent(...),
        ];

        yield [
            ResponseCode::RESET_CONTENT,
            HttpException::fromResetContent(...),
        ];

        yield [
            ResponseCode::PARTIAL_CONTENT,
            HttpException::fromPartialContent(...),
        ];

        yield [
            ResponseCode::MULTI_STATUS,
            HttpException::fromMultiStatus(...),
        ];

        yield [
            ResponseCode::ALREADY_REPORTED,
            HttpException::fromAlreadyReported(...),
        ];

        yield [
            ResponseCode::IM_USED,
            HttpException::fromImUsed(...),
        ];

        yield [
            ResponseCode::MULTIPLE_CHOICES,
            HttpException::fromMultipleChoices(...),
        ];

        yield [
            ResponseCode::MOVED_PERMANENTLY,
            HttpException::fromMovedPermanently(...),
        ];

        yield [
            ResponseCode::FOUND,
            HttpException::fromFound(...),
        ];

        yield [
            ResponseCode::SEE_OTHER,
            HttpException::fromSeeOther(...),
        ];

        yield [
            ResponseCode::NOT_MODIFIED,
            HttpException::fromNotModified(...),
        ];

        yield [
            ResponseCode::USE_PROXY,
            HttpException::fromUseProxy(...),
        ];

        yield [
            ResponseCode::SWITCH_PROXY,
            HttpException::fromSwitchProxy(...),
        ];

        yield [
            ResponseCode::TEMPORARY_REDIRECT,
            HttpException::fromTemporaryRedirect(...),
        ];

        yield [
            ResponseCode::PERMANENT_REDIRECT,
            HttpException::fromPermanentRedirect(...),
        ];

        yield [
            ResponseCode::BAD_REQUEST,
            HttpException::fromBadRequest(...),
        ];

        yield [
            ResponseCode::UNAUTHORIZED,
            HttpException::fromUnauthorized(...),
        ];

        yield [
            ResponseCode::PAYMENT_REQUIRED,
            HttpException::fromPaymentRequired(...),
        ];

        yield [
            ResponseCode::FORBIDDEN,
            HttpException::fromForbidden(...),
        ];

        yield [
            ResponseCode::NOT_FOUND,
            HttpException::fromNotFound(...),
        ];

        yield [
            ResponseCode::METHOD_NOT_ALLOWED,
            HttpException::fromMethodNotAllowed(...),
        ];

        yield [
            ResponseCode::NOT_ACCEPTABLE,
            HttpException::fromNotAcceptable(...),
        ];

        yield [
            ResponseCode::PROXY_AUTHENTICATION_REQUIRED,
            HttpException::fromProxyAuthenticationRequired(...),
        ];

        yield [
            ResponseCode::REQUEST_TIMEOUT,
            HttpException::fromRequestTimeout(...),
        ];

        yield [
            ResponseCode::CONFLICT,
            HttpException::fromConflict(...),
        ];

        yield [
            ResponseCode::GONE,
            HttpException::fromGone(...),
        ];

        yield [
            ResponseCode::LENGTH_REQUIRED,
            HttpException::fromLengthRequired(...),
        ];

        yield [
            ResponseCode::PRECONDITION_FAILED,
            HttpException::fromPreconditionFailed(...),
        ];

        yield [
            ResponseCode::PAYLOAD_TOO_LARGE,
            HttpException::fromPayloadTooLarge(...),
        ];

        yield [
            ResponseCode::URI_TOO_LONG,
            HttpException::fromUriTooLong(...),
        ];

        yield [
            ResponseCode::UNSUPPORTED_MEDIA_TYPE,
            HttpException::fromUnsupportedMediaType(...),
        ];

        yield [
            ResponseCode::RANGE_NOT_SATISFIABLE,
            HttpException::fromRangeNotSatisfiable(...),
        ];

        yield [
            ResponseCode::EXPECTATION_FAILED,
            HttpException::fromExpectationFailed(...),
        ];

        yield [
            ResponseCode::MISDIRECTED_REQUEST,
            HttpException::fromMisdirectedRequest(...),
        ];

        yield [
            ResponseCode::UNPROCESSABLE_ENTITY,
            HttpException::fromUnprocessableEntity(...),
        ];

        yield [
            ResponseCode::LOCKED,
            HttpException::fromLocked(...),
        ];

        yield [
            ResponseCode::FAILED_DEPENDENCY,
            HttpException::fromFailedDependency(...),
        ];

        yield [
            ResponseCode::TOO_EARLY,
            HttpException::fromTooEarly(...),
        ];

        yield [
            ResponseCode::UPGRADE_REQUIRED,
            HttpException::fromUpgradeRequired(...),
        ];

        yield [
            ResponseCode::PRECONDITION_REQUIRED,
            HttpException::fromPreconditionRequired(...),
        ];

        yield [
            ResponseCode::TOO_MANY_REQUESTS,
            HttpException::fromTooManyRequests(...),
        ];

        yield [
            ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE,
            HttpException::fromRequestHeaderFieldsTooLarge(...),
        ];

        yield [
            ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS,
            HttpException::fromUnavailableForLegalReasons(...),
        ];

        yield [
            ResponseCode::INTERNAL_SERVER_ERROR,
            HttpException::fromInternalServerError(...),
        ];

        yield [
            ResponseCode::NOT_IMPLEMENTED,
            HttpException::fromNotImplemented(...),
        ];

        yield [
            ResponseCode::BAD_GATEWAY,
            HttpException::fromBadGateway(...),
        ];

        yield [
            ResponseCode::SERVICE_UNAVAILABLE,
            HttpException::fromServiceUnavailable(...),
        ];

        yield [
            ResponseCode::GATEWAY_TIMEOUT,
            HttpException::fromGatewayTimeout(...),
        ];

        yield [
            ResponseCode::HTTP_VERSION_NOT_SUPPORTED,
            HttpException::fromHttpVersionNotSupported(...),
        ];

        yield [
            ResponseCode::VARIANT_ALSO_NEGOTIATES,
            HttpException::fromVariantAlsoNegotiates(...),
        ];

        yield [
            ResponseCode::INSUFFICIENT_STORAGE,
            HttpException::fromInsufficientStorage(...),
        ];

        yield [
            ResponseCode::LOOP_DETECTED,
            HttpException::fromLoopDetected(...),
        ];

        yield [
            ResponseCode::NOT_EXTENDED,
            HttpException::fromNotExtended(...),
        ];

        yield [
            ResponseCode::NETWORK_AUTHENTICATION_REQUIRED,
            HttpException::fromNetworkAuthenticationRequired(...),
        ];
    }

    /**
     * @param \Closure(): HttpException $factory
     */
    #[DataProvider('fromFactoryDataProvider')]
    public function testFromFactory(
        ResponseCode $expectedCode,
        \Closure $factory,
    ): void {
        $exception = $factory();

        self::assertInstanceOf(HttpException::class, $exception);
        self::assertSame($expectedCode, $exception->responseCode);
    }

    public function testFromFactoryWithPrevious(): void
    {
        $previous = new \RuntimeException('original');
        $exception = HttpException::fromNotFound($previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
