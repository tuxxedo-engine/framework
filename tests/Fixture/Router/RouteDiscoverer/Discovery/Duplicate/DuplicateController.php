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

namespace Fixture\Router\RouteDiscoverer\Discovery\Duplicate;

use Tuxxedo\Router\Attribute\Route\Get;

class DuplicateController
{
    #[Get(uri: '/first', name: 'shared')]
    public function first(): void
    {
    }

    #[Get(uri: '/second', name: 'shared')]
    public function second(): void
    {
    }
}
