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

    /**
     * @throws ConfigException
     */
    public function path(
        string $path,
    ): mixed;
}
