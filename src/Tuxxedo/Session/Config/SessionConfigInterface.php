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

namespace Tuxxedo\Session\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Http\SameSite;

#[DefaultImplementation(class: SessionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface SessionConfigInterface
{
    public int $lifetime {
        get;
    }

    public string $path {
        get;
    }

    public string $domain {
        get;
    }

    public bool $httpOnly {
        get;
    }

    public bool $secure {
        get;
    }

    public SameSite $sameSite {
        get;
    }
}
