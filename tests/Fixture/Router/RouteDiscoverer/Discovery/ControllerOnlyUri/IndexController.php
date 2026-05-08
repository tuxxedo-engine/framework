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

namespace Fixture\Router\RouteDiscoverer\Discovery\ControllerOnlyUri;

use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route\Get;

#[Controller(uri: '/users')]
class IndexController
{
    #[Get]
    public function index(): void
    {
    }

    #[Get]
    public function details(): void
    {
    }
}
