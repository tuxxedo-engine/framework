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

use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;

interface HeaderContextInterface
{
    /**
     * @return HeaderInterface[]
     */
    public function all(): array;

    public function has(
        string $name,
    ): bool;

    /**
     * @throws HttpException
     */
    public function getInt(
        string $name,
    ): int;

    /**
     * @throws HttpException
     */
    public function getBool(
        string $name,
    ): bool;

    /**
     * @throws HttpException
     */
    public function getFloat(
        string $name,
    ): float;

    /**
     * @throws HttpException
     */
    public function getString(
        string $name,
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
}
