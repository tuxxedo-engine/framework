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

    public function __construct(ResponseCode $responseCode)
    {
        $this->responseCode = $responseCode;

        parent::__construct(
            message: $responseCode->getStatusText(),
        );
    }

    public function send(): ResponseInterface
    {
        return new Response(
            responseCode: $this->responseCode,
        );
    }

    public static function fromBadRequest(): self
    {
        return new self(
            responseCode: ResponseCode::BAD_REQUEST,
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

    public static function fromUnauthorized(): self
    {
        return new self(
            responseCode: ResponseCode::UNAUTHORIZED,
        );
    }

    public static function fromMethodNotAllowed(): self
    {
        return new self(
            responseCode: ResponseCode::METHOD_NOT_ALLOWED,
        );
    }

    public static function fromUnprocessableEntity(): self
    {
        return new self(
            responseCode: ResponseCode::UNPROCESSABLE_ENTITY,
        );
    }

    public static function fromInternalServerError(): self
    {
        return new self(
            responseCode: ResponseCode::INTERNAL_SERVER_ERROR,
        );
    }
}
