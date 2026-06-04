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

namespace Tuxxedo\Http;

use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseCodeInterface;
use Tuxxedo\Http\Response\ResponseExceptionInterface;
use Tuxxedo\Http\Response\ResponseInterface;

class HttpException extends \Exception implements ResponseCodeInterface, ResponseExceptionInterface
{
    public readonly ResponseCode $responseCode;

    public function __construct(
        ResponseCode|int $responseCode,
        ?\Throwable $previous = null,
    ) {
        $this->responseCode = $responseCode instanceof ResponseCode
            ? $responseCode
            : ResponseCode::from($responseCode);

        parent::__construct(
            message: $this->responseCode->getStatusText(),
            previous: $previous,
        );
    }

    public function toResponse(): ResponseInterface
    {
        return new Response(
            responseCode: $this->responseCode,
        );
    }

    public static function fromBadRequest(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::BAD_REQUEST,
            previous: $exception,
        );
    }

    public static function fromUnauthorized(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::UNAUTHORIZED,
            previous: $exception,
        );
    }

    public static function fromPaymentRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::PAYMENT_REQUIRED,
            previous: $exception,
        );
    }

    public static function fromForbidden(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::FORBIDDEN,
            previous: $exception,
        );
    }

    public static function fromNotFound(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::NOT_FOUND,
            previous: $exception,
        );
    }

    public static function fromMethodNotAllowed(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::METHOD_NOT_ALLOWED,
            previous: $exception,
        );
    }

    public static function fromNotAcceptable(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::NOT_ACCEPTABLE,
            previous: $exception,
        );
    }

    public static function fromProxyAuthenticationRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::PROXY_AUTHENTICATION_REQUIRED,
            previous: $exception,
        );
    }

    public static function fromRequestTimeout(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::REQUEST_TIMEOUT,
            previous: $exception,
        );
    }

    public static function fromConflict(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::CONFLICT,
            previous: $exception,
        );
    }

    public static function fromGone(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::GONE,
            previous: $exception,
        );
    }

    public static function fromLengthRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::LENGTH_REQUIRED,
            previous: $exception,
        );
    }

    public static function fromPreconditionFailed(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::PRECONDITION_FAILED,
            previous: $exception,
        );
    }

    public static function fromPayloadTooLarge(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::PAYLOAD_TOO_LARGE,
            previous: $exception,
        );
    }

    public static function fromUriTooLong(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::URI_TOO_LONG,
            previous: $exception,
        );
    }

    public static function fromUnsupportedMediaType(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::UNSUPPORTED_MEDIA_TYPE,
            previous: $exception,
        );
    }

    public static function fromRangeNotSatisfiable(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::RANGE_NOT_SATISFIABLE,
            previous: $exception,
        );
    }

    public static function fromExpectationFailed(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::EXPECTATION_FAILED,
            previous: $exception,
        );
    }

    public static function fromMisdirectedRequest(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::MISDIRECTED_REQUEST,
            previous: $exception,
        );
    }

    public static function fromUnprocessableEntity(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::UNPROCESSABLE_ENTITY,
            previous: $exception,
        );
    }

    public static function fromLocked(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::LOCKED,
            previous: $exception,
        );
    }

    public static function fromFailedDependency(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::FAILED_DEPENDENCY,
            previous: $exception,
        );
    }

    public static function fromTooEarly(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::TOO_EARLY,
            previous: $exception,
        );
    }

    public static function fromUpgradeRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::UPGRADE_REQUIRED,
            previous: $exception,
        );
    }

    public static function fromPreconditionRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::PRECONDITION_REQUIRED,
            previous: $exception,
        );
    }

    public static function fromTooManyRequests(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::TOO_MANY_REQUESTS,
            previous: $exception,
        );
    }

    public static function fromRequestHeaderFieldsTooLarge(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE,
            previous: $exception,
        );
    }

    public static function fromUnavailableForLegalReasons(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS,
            previous: $exception,
        );
    }

    public static function fromInternalServerError(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::INTERNAL_SERVER_ERROR,
            previous: $exception,
        );
    }

    public static function fromNotImplemented(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::NOT_IMPLEMENTED,
            previous: $exception,
        );
    }

    public static function fromBadGateway(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::BAD_GATEWAY,
            previous: $exception,
        );
    }

    public static function fromServiceUnavailable(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::SERVICE_UNAVAILABLE,
            previous: $exception,
        );
    }

    public static function fromGatewayTimeout(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::GATEWAY_TIMEOUT,
            previous: $exception,
        );
    }

    public static function fromHttpVersionNotSupported(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::HTTP_VERSION_NOT_SUPPORTED,
            previous: $exception,
        );
    }

    public static function fromVariantAlsoNegotiates(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::VARIANT_ALSO_NEGOTIATES,
            previous: $exception,
        );
    }

    public static function fromInsufficientStorage(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::INSUFFICIENT_STORAGE,
            previous: $exception,
        );
    }

    public static function fromLoopDetected(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::LOOP_DETECTED,
            previous: $exception,
        );
    }

    public static function fromNotExtended(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::NOT_EXTENDED,
            previous: $exception,
        );
    }

    public static function fromNetworkAuthenticationRequired(
        ?\Throwable $exception = null,
    ): self {
        return new self(
            responseCode: ResponseCode::NETWORK_AUTHENTICATION_REQUIRED,
            previous: $exception,
        );
    }
}
