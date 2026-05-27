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

namespace Tuxxedo\Config;

use Tuxxedo\Container\DefaultImplementation;

#[DefaultImplementation(class: Config::class)]
interface ConfigInterface
{
    public function has(
        string $path,
    ): bool;

    public function isNull(
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
    public function int(
        string $path,
    ): int;

    public function isInt(
        string $path,
    ): bool;

    /**
     * @throws ConfigException
     */
    public function bool(
        string $path,
    ): bool;

    public function isBool(
        string $path,
    ): bool;

    /**
     * @throws ConfigException
     */
    public function float(
        string $path,
    ): float;

    public function isFloat(
        string $path,
    ): bool;

    /**
     * @throws ConfigException
     */
    public function string(
        string $path,
    ): string;

    public function isString(
        string $path,
    ): bool;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum
     *
     * @throws ConfigException
     */
    public function enum(
        string $path,
        string $enum,
    ): object;
}
