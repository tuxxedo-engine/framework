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

interface EnvLoaderInterface
{
    public function has(
        string $variable,
    ): bool;

    /**
     * @throws EnvException
     */
    public function value(
        string $variable,
    ): string;
}
