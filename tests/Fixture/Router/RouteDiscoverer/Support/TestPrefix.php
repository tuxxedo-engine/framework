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

namespace Fixture\Router\RouteDiscoverer\Support;

use Tuxxedo\Router\PrefixInterface;

readonly class TestPrefix implements PrefixInterface
{
    public string $uri;

    public function __construct()
    {
        $this->uri = '/api';
    }
}
