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

use Tuxxedo\Router\PrefixDefaultsInterface;

readonly class TestPrefixWithDefaults implements PrefixDefaultsInterface
{
    public string $uri;

    public function __construct()
    {
        $this->uri = '/{locale:[a-z]{2}}';
    }

    public function getDefaultValue(
        string $argument,
    ): mixed {
        return match ($argument) {
            'locale' => 'en',
            default => null,
        };
    }
}
