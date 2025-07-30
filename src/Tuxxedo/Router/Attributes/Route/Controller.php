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

namespace Tuxxedo\Router\Attributes\Route;

use Tuxxedo\Router\RoutePriority;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
readonly class Controller
{
    public function __construct(
        public string $uri,
    ) {
    }
}
