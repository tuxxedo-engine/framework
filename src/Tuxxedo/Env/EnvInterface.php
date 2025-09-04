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

namespace Tuxxedo\Env;

interface EnvInterface
{
    public function has(
        string $variable,
    ): bool;

    /**
     * @throws EnvException
     */
    public function getInt(
        string $variable,
    ): int;

    /**
     * @throws EnvException
     */
    public function getBool(
        string $variable,
    ): bool;

    /**
     * @throws EnvException
     */
    public function getFloat(
        string $variable,
    ): float;

    /**
     * @throws EnvException
     */
    public function getString(
        string $variable,
    ): string;
}
