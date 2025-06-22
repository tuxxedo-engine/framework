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
use UnitEnum as T;

interface InputContextInterface
{
    /**
     * @return mixed[]
     */
    public function all(): array;

    public function has(
        string $name,
    ): bool;

    public function getRaw(
        string $name,
    ): mixed;

    public function getInt(
        string $name,
        int $default = 0,
    ): int;

    public function getBool(
        string $name,
        bool $default = false,
    ): bool;

    /**
     * @param '.'|',' $decimalPoint
     */
    public function getFloat(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.', // @todo Consider a flag for both
    ): float;

    public function getString(
        string $name,
        string $default = '',
    ): string;

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @return T&\UnitEnum
     *
     * @throws HttpException
     */
    public function getEnum(
        string $name,
        string $enum,
    ): object;

    /**
     * @return int[]
     */
    public function getArrayOfInt(
        string $name,
    ): array;

    /**
     * @return bool[]
     */
    public function getArrayOfBool(
        string $name,
    ): array;

    /**
     * @param '.'|',' $decimalPoint
     * @return float[]
     */
    public function getArrayOfFloat(
        string $name,
        string $decimalPoint = '.', // @todo Consider a flag for both
    ): array;

    /**
     * @return string[]
     */
    public function getArrayOfString(
        string $name,
    ): array;

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @return array<T&\UnitEnum>
     *
     * @throws HttpException
     */
    public function getArrayOfEnum(
        string $name,
        string $enum,
    ): array;
}
