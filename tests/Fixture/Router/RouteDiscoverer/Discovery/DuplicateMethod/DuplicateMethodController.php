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

namespace Fixture\Router\RouteDiscoverer\Discovery\DuplicateMethod;

use Tuxxedo\Http\Method;
use Tuxxedo\Router\Attribute\Route;

class DuplicateMethodController
{
    #[Route(
        uri: '/contact/{id}',
        method: [
            Method::GET,
            Method::GET,
            Method::POST,
        ],
    )]
    public function contact(
        int $id,
    ): void {
    }
}
