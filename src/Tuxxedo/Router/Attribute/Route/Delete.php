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

namespace Tuxxedo\Router\Attribute\Route;

use Tuxxedo\Http\Method;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Router\PrefixInterface;
use Tuxxedo\Router\RoutePriority;

#[\Attribute(flags: \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Delete extends Route
{
    /**
     * @param class-string<PrefixInterface>|string|null $prefix
     */
    public function __construct(
        ?string $path = null,
        ?string $name = null,
        bool $trailingSlash = false,
        ?string $prefix = null,
        RoutePriority $priority = RoutePriority::NORMAL,
    ) {
        parent::__construct(
            path: $path,
            method: [
                Method::DELETE,
            ],
            name: $name,
            trailingSlash: $trailingSlash,
            prefix: $prefix,
            priority: $priority,
        );
    }
}
