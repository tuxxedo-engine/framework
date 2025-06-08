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

use Tuxxedo\Http\Method;

class EnvironmentServerContext implements ServerContextInterface
{
    public bool $https {
        get {
            return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }
    }

    public Method $method {
        get {
            /** @var string $method */
            $method = $_SERVER['REQUEST_METHOD'];

            return Method::from($method);
        }
    }

    public string $uri {
        get {
            /** @var string */
            return $_SERVER['REQUEST_URI'];
        }
    }
}
