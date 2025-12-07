<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
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
    ) {
        $this->responseCode = $responseCode instanceof ResponseCode
            ? $responseCode
            : ResponseCode::from($responseCode);

        parent::__construct(
            message: $this->responseCode->getStatusText(),
        );
    }

    public function send(): ResponseInterface
    {
        return new Response(
            responseCode: $this->responseCode,
        );
    }

    public static function fromContinue(): self
    {
        return new self(
            responseCode: ResponseCode::CONTINUE,
        );
    }

    public static function fromSwitchingProtocols(): self
    {
        return new self(
            responseCode: ResponseCode::SWITCHING_PROTOCOLS,
        );
    }

    public static function fromProcessing(): self
    {
        return new self(
            responseCode: ResponseCode::PROCESSING,
        );
    }

    public static function fromEarlyHints(): self
    {
        return new self(
            responseCode: ResponseCode::EARLY_HINTS,
        );
    }

    public static function fromOk(): self
    {
        return new self(
            responseCode: ResponseCode::OK,
        );
    }

    public static function fromCreated(): self
    {
        return new self(
            responseCode: ResponseCode::CREATED,
        );
    }

    public static function fromAccepted(): self
    {
        return new self(
            responseCode: ResponseCode::ACCEPTED,
        );
    }

    public static function fromNonAuthoritativeInformation(): self
    {
        return new self(
            responseCode: ResponseCode::NON_AUTHORITATIVE_INFORMATION,
        );
    }

    public static function fromNoContent(): self
    {
        return new self(
            responseCode: ResponseCode::NO_CONTENT,
        );
    }

    public static function fromResetContent(): self
    {
        return new self(
            responseCode: ResponseCode::RESET_CONTENT,
        );
    }

    public static function fromPartialContent(): self
    {
        return new self(
            responseCode: ResponseCode::PARTIAL_CONTENT,
        );
    }

    public static function fromMultiStatus(): self
    {
        return new self(
            responseCode: ResponseCode::MULTI_STATUS,
        );
    }

    public static function fromAlreadyReported(): self
    {
        return new self(
            responseCode: ResponseCode::ALREADY_REPORTED,
        );
    }

    public static function fromImUsed(): self
    {
        return new self(
            responseCode: ResponseCode::IM_USED,
        );
    }

    public static function fromMultipleChoices(): self
    {
        return new self(
            responseCode: ResponseCode::MULTIPLE_CHOICES,
        );
    }

    public static function fromMovedPermanently(): self
    {
        return new self(
            responseCode: ResponseCode::MOVED_PERMANENTLY,
        );
    }

    public static function fromFound(): self
    {
        return new self(
            responseCode: ResponseCode::FOUND,
        );
    }

    public static function fromSeeOther(): self
    {
        return new self(
            responseCode: ResponseCode::SEE_OTHER,
        );
    }

    public static function fromNotModified(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_MODIFIED,
        );
    }

    public static function fromUseProxy(): self
    {
        return new self(
            responseCode: ResponseCode::USE_PROXY,
        );
    }

    public static function fromSwitchProxy(): self
    {
        return new self(
            responseCode: ResponseCode::SWITCH_PROXY,
        );
    }

    public static function fromTemporaryRedirect(): self
    {
        return new self(
            responseCode: ResponseCode::TEMPORARY_REDIRECT,
        );
    }

    public static function fromPermanentRedirect(): self
    {
        return new self(
            responseCode: ResponseCode::PERMANENT_REDIRECT,
        );
    }

    public static function fromBadRequest(): self
    {
        return new self(
            responseCode: ResponseCode::BAD_REQUEST,
        );
    }

    public static function fromUnauthorized(): self
    {
        return new self(
            responseCode: ResponseCode::UNAUTHORIZED,
        );
    }

    public static function fromPaymentRequired(): self
    {
        return new self(
            responseCode: ResponseCode::PAYMENT_REQUIRED,
        );
    }

    public static function fromForbidden(): self
    {
        return new self(
            responseCode: ResponseCode::FORBIDDEN,
        );
    }

    public static function fromNotFound(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_FOUND,
        );
    }

    public static function fromMethodNotAllowed(): self
    {
        return new self(
            responseCode: ResponseCode::METHOD_NOT_ALLOWED,
        );
    }

    public static function fromNotAcceptable(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_ACCEPTABLE,
        );
    }

    public static function fromProxyAuthenticationRequired(): self
    {
        return new self(
            responseCode: ResponseCode::PROXY_AUTHENTICATION_REQUIRED,
        );
    }

    public static function fromRequestTimeout(): self
    {
        return new self(
            responseCode: ResponseCode::REQUEST_TIMEOUT,
        );
    }

    public static function fromConflict(): self
    {
        return new self(
            responseCode: ResponseCode::CONFLICT,
        );
    }

    public static function fromGone(): self
    {
        return new self(
            responseCode: ResponseCode::GONE,
        );
    }

    public static function fromLengthRequired(): self
    {
        return new self(
            responseCode: ResponseCode::LENGTH_REQUIRED,
        );
    }

    public static function fromPreconditionFailed(): self
    {
        return new self(
            responseCode: ResponseCode::PRECONDITION_FAILED,
        );
    }

    public static function fromPayloadTooLarge(): self
    {
        return new self(
            responseCode: ResponseCode::PAYLOAD_TOO_LARGE,
        );
    }

    public static function fromUriTooLong(): self
    {
        return new self(
            responseCode: ResponseCode::URI_TOO_LONG,
        );
    }

    public static function fromUnsupportedMediaType(): self
    {
        return new self(
            responseCode: ResponseCode::UNSUPPORTED_MEDIA_TYPE,
        );
    }

    public static function fromRangeNotSatisfiable(): self
    {
        return new self(
            responseCode: ResponseCode::RANGE_NOT_SATISFIABLE,
        );
    }

    public static function fromExpectationFailed(): self
    {
        return new self(
            responseCode: ResponseCode::EXPECTATION_FAILED,
        );
    }

    public static function fromMisdirectedRequest(): self
    {
        return new self(
            responseCode: ResponseCode::MISDIRECTED_REQUEST,
        );
    }

    public static function fromUnprocessableEntity(): self
    {
        return new self(
            responseCode: ResponseCode::UNPROCESSABLE_ENTITY,
        );
    }

    public static function fromLocked(): self
    {
        return new self(
            responseCode: ResponseCode::LOCKED,
        );
    }

    public static function fromFailedDependency(): self
    {
        return new self(
            responseCode: ResponseCode::FAILED_DEPENDENCY,
        );
    }

    public static function fromTooEarly(): self
    {
        return new self(
            responseCode: ResponseCode::TOO_EARLY,
        );
    }

    public static function fromUpgradeRequired(): self
    {
        return new self(
            responseCode: ResponseCode::UPGRADE_REQUIRED,
        );
    }

    public static function fromPreconditionRequired(): self
    {
        return new self(
            responseCode: ResponseCode::PRECONDITION_REQUIRED,
        );
    }

    public static function fromTooManyRequests(): self
    {
        return new self(
            responseCode: ResponseCode::TOO_MANY_REQUESTS,
        );
    }

    public static function fromRequestHeaderFieldsTooLarge(): self
    {
        return new self(
            responseCode: ResponseCode::REQUEST_HEADER_FIELDS_TOO_LARGE,
        );
    }

    public static function fromUnavailableForLegalReasons(): self
    {
        return new self(
            responseCode: ResponseCode::UNAVAILABLE_FOR_LEGAL_REASONS,
        );
    }

    public static function fromInternalServerError(): self
    {
        return new self(
            responseCode: ResponseCode::INTERNAL_SERVER_ERROR,
        );
    }

    public static function fromNotImplemented(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_IMPLEMENTED,
        );
    }

    public static function fromBadGateway(): self
    {
        return new self(
            responseCode: ResponseCode::BAD_GATEWAY,
        );
    }

    public static function fromServiceUnavailable(): self
    {
        return new self(
            responseCode: ResponseCode::SERVICE_UNAVAILABLE,
        );
    }

    public static function fromGatewayTimeout(): self
    {
        return new self(
            responseCode: ResponseCode::GATEWAY_TIMEOUT,
        );
    }

    public static function fromHttpVersionNotSupported(): self
    {
        return new self(
            responseCode: ResponseCode::HTTP_VERSION_NOT_SUPPORTED,
        );
    }

    public static function fromVariantAlsoNegotiates(): self
    {
        return new self(
            responseCode: ResponseCode::VARIANT_ALSO_NEGOTIATES,
        );
    }

    public static function fromInsufficientStorage(): self
    {
        return new self(
            responseCode: ResponseCode::INSUFFICIENT_STORAGE,
        );
    }

    public static function fromLoopDetected(): self
    {
        return new self(
            responseCode: ResponseCode::LOOP_DETECTED,
        );
    }

    public static function fromNotExtended(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_EXTENDED,
        );
    }

    public static function fromNetworkAuthenticationRequired(): self
    {
        return new self(
            responseCode: ResponseCode::NETWORK_AUTHENTICATION_REQUIRED,
        );
    }
}
