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

namespace Fixture\Router\RouteDiscoverer\Discovery\TypedImplicit;

use Tuxxedo\Router\Attribute\Route\Get;

class TypedImplicitController
{
    #[Get(uri: '/users/{id}')]
    public function show(
        int $id,
    ): void {
    }
}
