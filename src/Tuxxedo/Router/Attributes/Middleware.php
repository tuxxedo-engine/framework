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

namespace Tuxxedo\Router\Attributes;

use Tuxxedo\Http\Request\Middleware\MiddlewareInterface;

#[\Attribute(flags: \Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Middleware
{
    /**
     * @param class-string<MiddlewareInterface> $name
     */
    public function __construct(
        public string $name,
    ) {
    }
}
