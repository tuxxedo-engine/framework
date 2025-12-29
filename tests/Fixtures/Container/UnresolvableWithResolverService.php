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

namespace Fixtures\Container;

use Fixtures\Container\Resolver\UnresolvableTypeResolver;

class UnresolvableWithResolverService
{
    public function __construct(
        #[UnresolvableTypeResolver] public readonly UnresolvableDependencyOne $dependency,
    ) {
    }
}
