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
use Tuxxedo\Http\Request\Context\InputContextInterface;

class StubInputContext implements InputContextInterface
{
    public function has(
        string $name,
    ): bool {
        return false;
    }

    public function raw(
        string $name,
        mixed $default = null,
    ): mixed {
        return $default;
    }

    public function rawArray(
        string $name,
        array $default = [],
    ): array {
        return $default;
    }

    public function int(
        string $name,
        int $default = 0,
    ): int {
        return $default;
    }

    public function bool(
        string $name,
        bool $default = false,
    ): bool {
        return $default;
    }

    public function float(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): float {
        return $default;
    }

    public function string(
        string $name,
        string $default = '',
    ): string {
        return $default;
    }

    public function enum(
        string $name,
        string $enum,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function arrayOfInt(
        string $name,
    ): array {
        return [];
    }

    public function arrayOfBool(
        string $name,
    ): array {
        return [];
    }

    public function arrayOfFloat(
        string $name,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): array {
        return [];
    }

    public function arrayOfString(
        string $name,
    ): array {
        return [];
    }

    public function arrayOfEnum(
        string $name,
        string $enum,
    ): array {
        throw HttpException::fromInternalServerError();
    }

    public function mapTo(
        string $name,
        string|object $className,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function mapToArrayOf(
        string $name,
        string|object $className,
    ): array {
        throw HttpException::fromInternalServerError();
    }

    public function jsonMapTo(
        string $name,
        string|object $className,
        int $flags = 0,
    ): object {
        throw HttpException::fromInternalServerError();
    }

    public function jsonMapToArrayOf(
        string $name,
        string|object $className,
        int $flags = 0,
    ): array {
        throw HttpException::fromInternalServerError();
    }
}
