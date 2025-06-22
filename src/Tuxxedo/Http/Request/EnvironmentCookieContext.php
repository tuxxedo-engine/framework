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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\WeightedHeader;

/**
 * @implements HeaderContextInterface<string, string>
 */
class EnvironmentCookieContext implements HeaderContextInterface
{
    public function all(): array
    {
        /** @var array<string, string> */
        return $_COOKIE;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $_COOKIE);
    }

    public function getInt(string $name): int
    {
        if (!\array_key_exists($name, $_COOKIE)) {
            throw HttpException::fromInternalServerError();
        }

        /** @var string $value */
        $value = $_COOKIE[$name];

        return (int) $value;
    }

    public function getBool(string $name): bool
    {
        if (!\array_key_exists($name, $_COOKIE)) {
            throw HttpException::fromInternalServerError();
        }

        /** @var string $value */
        $value = $_COOKIE[$name];

        return (bool) $value;
    }

    public function getFloat(string $name): float
    {
        if (!\array_key_exists($name, $_COOKIE)) {
            throw HttpException::fromInternalServerError();
        }

        /** @var string $value */
        $value = $_COOKIE[$name];

        return (float) $value;
    }

    public function getString(string $name): string
    {
        if (!\array_key_exists($name, $_COOKIE)) {
            throw HttpException::fromInternalServerError();
        }

        /** @var string */
        return $_COOKIE[$name];
    }

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @return T&\UnitEnum
     *
     * @throws HttpException
     */
    public function getEnum(string $name, string $enum): object
    {
        if (!\enum_exists($enum)) {
            throw HttpException::fromInternalServerError();
        }

        if (!\array_key_exists($name, $_COOKIE)) {
            throw HttpException::fromInternalServerError();
        }

        foreach ($enum::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        throw HttpException::fromInternalServerError();
    }
}
