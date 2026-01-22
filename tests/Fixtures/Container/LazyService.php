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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;

#[DefaultInitializer(
    static function (
        ContainerInterface $container,
        array $arguments,
    ): LazyService {
        return new LazyService(
            name: \array_key_exists('name', $arguments) && \is_string($arguments['name'])
                ? $arguments['name']
                : 'baz',
        );
    },
)]
class LazyService
{
    final public function __construct(
        public readonly string $name,
    ) {
    }
}
