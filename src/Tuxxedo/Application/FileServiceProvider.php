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

namespace Tuxxedo\Application;

use Tuxxedo\Container\ContainerInterface;

class FileServiceProvider implements ServiceProviderInterface
{
    public function __construct(
        public string $file,
    ) {
    }

    public function load(ContainerInterface $container): void
    {
        $provider = (static fn (string $file): mixed => require $file)($this->file);

        if ($provider instanceof \Closure) {
            $container->call($provider);
        }
    }
}
