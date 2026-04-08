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
readonly class Options extends Route
{
    /**
     * @param class-string<PrefixInterface>|null $prefix
     */
    public function __construct(
        ?string $uri = null,
        ?string $name = null,
        bool $trailingSlash = false,
        ?string $prefix = null,
        RoutePriority $priority = RoutePriority::NORMAL,
    ) {
        parent::__construct(
            uri: $uri,
            method: [
                Method::OPTIONS,
            ],
            name: $name,
            trailingSlash: $trailingSlash,
            prefix: $prefix,
            priority: $priority,
        );
    }
}
