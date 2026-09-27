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

namespace Tuxxedo\Application\Config;

use Tuxxedo\Application\Environment;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: AppConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface AppConfigInterface
{
    public string $name {
        get;
    }

    public string $version {
        get;
    }

    public Environment $environment {
        get;
    }

    public string $url {
        get;
    }
}
