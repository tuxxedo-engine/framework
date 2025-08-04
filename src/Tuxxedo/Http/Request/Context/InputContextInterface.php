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

namespace Tuxxedo\Http\Request\Context;

use Tuxxedo\Collections\CollectionInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Mapper\MapperException;

interface InputContextInterface
{
    /**
     * @return mixed[]
     */
    public function all(): array;

    /**
     * @return CollectionInterface<array-key, mixed>
     */
    public function toCollection(): CollectionInterface;

    public function has(
        string $name,
    ): bool;

    public function getRaw(
        string $name,
        mixed $default = null,
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
     * @param ','|'.' $thousandSeparator
     */
    public function getFloat(
        string $name,
        float $default = 0.0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): float;

    public function getString(
        string $name,
        string $default = '',
    ): string;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum&\UnitEnum
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
     * @param ','|'.' $thousandSeparator
     * @return float[]
     */
    public function getArrayOfFloat(
        string $name,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
    ): array;

    /**
     * @return string[]
     */
    public function getArrayOfString(
        string $name,
    ): array;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return array<TEnum&\UnitEnum>
     *
     * @throws HttpException
     */
    public function getArrayOfEnum(
        string $name,
        string $enum,
    ): array;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $class
     * @return TClassName
     *
     * @throws HttpException
     * @throws MapperException
     */
    public function mapTo(
        string $name,
        string|object $class,
    ): object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $class
     * @return TClassName[]
     *
     * @throws HttpException
     * @throws MapperException
     */
    public function mapToArrayOf(
        string $name,
        string|object $class,
    ): array;
}
