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

use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseCodeInterface;

class HttpException extends \Exception implements ResponseCodeInterface
{
    public readonly ResponseCode $responseCode;

    public function __construct(ResponseCode $responseCode)
    {
        $this->responseCode = $responseCode;

        parent::__construct(
            message: $responseCode->getStatusText(),
        );
    }

    public static function fromNotFound(): self
    {
        return new self(
            responseCode: ResponseCode::NOT_FOUND,
        );
    }

    public static function fromInternalServerError(): self
    {
        return new self(
            responseCode: ResponseCode::INTERNAL_SERVER_ERROR,
        );
    }

    public static function fromUnauthorized(): self
    {
        return new self(
            responseCode: ResponseCode::UNAUTHORIZED,
        );
    }
}
