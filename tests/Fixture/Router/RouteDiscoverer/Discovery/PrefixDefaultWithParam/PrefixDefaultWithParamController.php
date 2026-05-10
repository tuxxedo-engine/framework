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

namespace Fixture\Router\RouteDiscoverer\Discovery\PrefixDefaultWithParam;

use Fixture\Router\RouteDiscoverer\Support\TestPrefixWithDefaults;
use Tuxxedo\Router\Attribute\Route\Get;

class PrefixDefaultWithParamController
{
    #[Get(uri: '/home', prefix: TestPrefixWithDefaults::class)]
    public function home(
        string $locale,
    ): void {
    }
}
