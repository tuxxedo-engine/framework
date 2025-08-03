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

use Tuxxedo\Http\Method;
use Tuxxedo\Router\Attributes\Route;
use Tuxxedo\Router\RoutePriority;

#[\Attribute(flags: \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Patch extends Route
{
    public function __construct(
        ?string $uri = null,
        RoutePriority $priority = RoutePriority::NORMAL,
    ) {
        parent::__construct(
            uri: $uri,
            methods: [
                Method::PATCH,
            ],
            priority: $priority,
        );
    }
}
