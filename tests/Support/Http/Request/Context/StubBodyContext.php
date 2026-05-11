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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\BodyContextInterface;

class StubBodyContext implements BodyContextInterface
{
    public function getStream()
    {
        throw HttpException::fromInternalServerError();
    }

    public function getRaw(): string
    {
        return '';
    }

    public function getJson(
        bool $associative = false,
        int $flags = 0,
    ): mixed {
        return null;
    }

    public function jsonMapTo(
        string|object $className,
        int $flags = 0,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function jsonMapToArrayOf(
        string|object $className,
        int $flags = 0,
    ): array {
        throw HttpException::fromInternalServerError();
    }
}
