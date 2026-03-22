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

namespace Tuxxedo\Router\Attribute;

use Tuxxedo\Router\PrefixInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
readonly class Controller
{
    /**
     * @param class-string<PrefixInterface>|null $prefix
     */
    public function __construct(
        public string $uri,
        public bool $autoIndex = true,
        public string $autoIndexMethodName = 'index',
        public bool $autoTrailingSlash = false,
        public ?string $prefix = null,
    ) {
    }
}
