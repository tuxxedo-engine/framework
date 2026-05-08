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

namespace Fixture\Router\RouteDiscoverer\Discovery\UnsupportedNativeType;

use Tuxxedo\Router\Attribute\Route\Get;

class UnsupportedNativeTypeController
{
    /**
     * @param mixed[] $value
     */
    #[Get(uri: '/values/{value}')]
    public function show(
        array $value,
    ): void {
    }
}
