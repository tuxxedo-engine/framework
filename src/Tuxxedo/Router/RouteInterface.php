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

namespace Tuxxedo\Router;

use Tuxxedo\Http\Method;

interface RouteInterface
{
    public ?Method $method {
        get;
    }

    public string $uri {
        get;
    }

    /**
     * @var class-string
     */
    public string $controller {
        get;
    }

    public string $action {
        get;
    }

    // @todo Add support for route arguments
}
