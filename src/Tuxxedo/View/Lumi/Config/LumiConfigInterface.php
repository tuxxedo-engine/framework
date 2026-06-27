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

namespace Tuxxedo\View\Lumi\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: LumiConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface LumiConfigInterface
{
    public string $directory {
        get;
    }

    public string $cacheDirectory {
        get;
    }

    public string $extension {
        get;
    }

    public bool $alwaysCompile {
        get;
    }

    public bool $disableErrorReporting {
        get;
    }
}
