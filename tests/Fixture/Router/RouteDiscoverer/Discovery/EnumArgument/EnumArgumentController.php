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

namespace Fixture\Router\RouteDiscoverer\Discovery\EnumArgument;

use Fixture\Router\RouteDiscoverer\Support\TestStringEnum;
use Fixture\Router\RouteDiscoverer\Support\TestUnitEnum;
use Tuxxedo\Router\Attribute\Route\Get;

class EnumArgumentController
{
    #[Get(path: '/feeds/{feed}')]
    public function feed(
        TestStringEnum $feed,
    ): void {
    }

    #[Get(path: '/units/{?unit}')]
    public function unit(
        ?TestUnitEnum $unit = null,
    ): void {
    }

    #[Get(path: '/explicit/{feed:[a-z.]+}')]
    public function explicit(
        TestStringEnum $feed,
    ): void {
    }
}
