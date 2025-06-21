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

class Request implements RequestInterface
{
    public function __construct(
        public private(set) ServerContextInterface $context,
    ) {
    }

    public static function createFromEnvironment(): Request
    {
        return new Request(
            context: new EnvironmentServerContext(),
        );
    }
}
