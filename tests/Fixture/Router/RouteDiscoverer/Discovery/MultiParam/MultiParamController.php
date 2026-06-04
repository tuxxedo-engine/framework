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

namespace Fixture\Router\RouteDiscoverer\Discovery\MultiParam;

use Tuxxedo\Router\Attribute\Route\Get;

class MultiParamController
{
    #[Get(path: '/users/{id}/{page}')]
    public function show(
        int $id,
        int $page,
    ): void {
    }
}
