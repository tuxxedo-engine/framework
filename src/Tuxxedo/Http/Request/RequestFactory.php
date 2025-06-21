<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Request;

// @todo Re-consider the factory approach
class RequestFactory
{
    final private function __construct()
    {
    }

    public static function createFromEnvironment(): Request
    {
        return new Request(
            context: new EnvironmentServerContext(),
        );
    }
}
