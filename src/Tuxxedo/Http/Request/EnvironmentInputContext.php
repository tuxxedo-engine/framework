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

use Tuxxedo\Http\HttpException;

class EnvironmentInputContext implements InputContextInterface
{
    /**
     * @param 0|1|2 $superglobal
     */
    public function __construct(
        private readonly int $superglobal,
    ) {
    }

    public function all(): array
    {
        return match ($this->superglobal) {
            \INPUT_GET => $_GET,
            \INPUT_POST => $_POST,
            \INPUT_COOKIE => $_COOKIE,
        };
    }

    public function has(string $name): bool
    {
        return \filter_has_var($this->superglobal, $name);
    }

    public function getRaw(string $name): mixed
    {
        $superglobal = match ($this->superglobal) {
            \INPUT_GET => $_GET,
            \INPUT_POST => $_POST,
            \INPUT_COOKIE => $_COOKIE,
        };

        if (!\array_key_exists($name, $superglobal)) {
            throw HttpException::fromInternalServerError();
        }

        return $superglobal[$name];
    }

    public function getInt(string $name, int $default = 0): int
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            return $default;
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_VALIDATE_INT);

        if (!\is_int($value)) {
            return $default;
        }

        return $value;
    }

    public function getBool(string $name, bool $default = false): bool
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            return $default;
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_VALIDATE_BOOL);

        if (!\is_bool($value)) {
            return $default;
        }

        return $value;
    }

    public function getFloat(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.',
    ): float {
        if (!\filter_has_var($this->superglobal, $name)) {
            return $default;
        }

        $value = \filter_input(
            $this->superglobal,
            $name,
            \FILTER_VALIDATE_FLOAT,
            [
                'options' => [
                    'decimal' => $decimalPoint,
                ],
            ],
        );

        if (!\is_float($value)) {
            return $default;
        }

        return $value;
    }

    public function getString(string $name, string $default = ''): string
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            return $default;
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_DEFAULT);

        if (!\is_string($value)) {
            return $default;
        }

        return $value;
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
        if (
            !\enum_exists($enum) ||
            !\filter_has_var($this->superglobal, $name)
        ) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_input($this->superglobal, $name);

        if (!\is_string($value)) {
            throw HttpException::fromInternalServerError();
        }

        foreach ($enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw HttpException::fromInternalServerError();
    }

    public function getArrayOfInt(string $name): array
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_VALIDATE_INT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $value;
    }

    public function getArrayOfBool(string $name): array
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_VALIDATE_BOOL, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $value;
    }

    public function getArrayOfFloat(string $name, string $decimalPoint = '.'): array
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_input(
            $this->superglobal,
            $name,
            \FILTER_VALIDATE_FLOAT,
            [
                'flags' => \FILTER_REQUIRE_ARRAY,
                'options' => [
                    'decimal' => $decimalPoint,
                ],
            ],
        );

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $value;
    }

    public function getArrayOfString(string $name): array
    {
        if (!\filter_has_var($this->superglobal, $name)) {
            throw HttpException::fromInternalServerError();
        }

        $value = \filter_input($this->superglobal, $name, \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($value)) {
            throw HttpException::fromInternalServerError();
        }

        return $value;
    }

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @return array<T&\UnitEnum>
     *
     * @throws HttpException
     */
    public function getArrayOfEnum(string $name, string $enum): array
    {
        if (
            !\enum_exists($enum) ||
            !\filter_has_var($this->superglobal, $name)
        ) {
            throw HttpException::fromInternalServerError();
        }

        $values = \filter_input($this->superglobal, $name, \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);

        if (!\is_array($values)) {
            throw HttpException::fromInternalServerError();
        }

        $cases = [];

        foreach ($enum::cases() as $case) {
            $cases[$case->name] = $case;
        }

        $enums = [];

        foreach ($values as $value) {
            if (!\array_key_exists($value, $cases)) {
                throw HttpException::fromInternalServerError();
            }

            $enums[] = $cases[$value];
        }

        return $enums;
    }
}
