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

enum InputContext
{
    case GET;
    case POST;
    case COOKIE;

    /**
     * @throws HttpException
     */
    public static function fromMethod(
        Method $method
    ): self {
        return match ($method) {
            Method::GET => self::GET,
            Method::POST => self::POST,
            default => throw HttpException::fromInternalServerError(),
        };
    }
}
