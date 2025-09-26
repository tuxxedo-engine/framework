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

namespace Tuxxedo\Config;

interface ConfigInterface
{
    public function has(
        string $path,
    ): bool;

    /**
     * @throws ConfigException
     */
    public function path(
        string $path,
    ): mixed;

    /**
     * @throws ConfigException
     */
    public function section(
        string $path,
    ): self;

    /**
     * @throws ConfigException
     */
    public function getInt(
        string $path,
    ): int;

    /**
     * @throws ConfigException
     */
    public function getBool(
        string $path,
    ): bool;

    /**
     * @throws ConfigException
     */
    public function getFloat(
        string $path,
    ): float;

    /**
     * @throws ConfigException
     */
    public function getString(
        string $path,
    ): string;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum
     *
     * @throws ConfigException
     */
    public function getEnum(
        string $path,
        string $enum,
    ): object;
}
