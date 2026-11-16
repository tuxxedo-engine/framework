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

namespace Fixture\Container;

use Tuxxedo\Container\DefaultFactory;

#[DefaultFactory(DefaultServiceFive::create(...))]
class DefaultServiceFive implements DefaultServiceFiveInterface
{
    private function __construct(
        public readonly CtorNoArgsService $dependency,
        public readonly string $name,
    ) {
    }

    public static function create(
        CtorNoArgsService $dependency,
        string $name = 'default',
    ): self {
        return new self(
            dependency: $dependency,
            name: $name,
        );
    }
}
