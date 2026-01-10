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

namespace Tuxxedo\Container;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DefaultImplementation
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public string $class,
    ) {
    }
}
