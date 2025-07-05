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

namespace Tuxxedo\Services;

use Tuxxedo\Container\Container;
use Tuxxedo\Http\Response\ResponseEmitter;
use Tuxxedo\Mapper\Mapper;

class EngineServiceProvider implements ServiceProviderInterface
{
    public function load(Container $container): void
    {
        $container->persistent(Mapper::class);
        $container->persistent(ResponseEmitter::class);
    }
}
