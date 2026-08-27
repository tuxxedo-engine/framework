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

namespace Tuxxedo\Debug\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: DebugConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface DebugConfigInterface
{
    public bool $alwaysShow {
        get;
    }

    public string $rootPath {
        get;
    }

    public bool $registerPhpErrorHandler {
        get;
    }
}
