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

namespace Tuxxedo\Console\Config;

use Tuxxedo\Application\Profile;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: ConsoleAppConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface ConsoleAppConfigInterface
{
    public string $name {
        get;
    }

    public string $version {
        get;
    }

    public Profile $profile {
        get;
    }

    public SuggestionPolicy $suggestionPolicy {
        get;
    }

    public HelpConfig $helpConfig {
        get;
    }
}
