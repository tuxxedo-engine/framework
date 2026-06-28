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

namespace Tuxxedo\Env;

interface EnvInterface
{
    public function has(
        string $key,
    ): bool;

    /**
     * @throws EnvException
     */
    public function string(
        string $key,
        ?string $default = null,
    ): string;

    /**
     * @throws EnvException
     */
    public function int(
        string $key,
        ?int $default = null,
    ): int;

    /**
     * @throws EnvException
     */
    public function bool(
        string $key,
        ?bool $default = null,
    ): bool;

    /**
     * @throws EnvException
     */
    public function float(
        string $key,
        ?float $default = null,
    ): float;

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @param T|null $default
     * @return T
     *
     * @throws EnvException
     */
    public function enum(
        string $key,
        string $enum,
        ?\UnitEnum $default = null,
    ): \UnitEnum;
}
