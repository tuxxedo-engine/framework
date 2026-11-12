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

namespace Tuxxedo\Logger\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\LogLevel;

#[DefaultImplementation(class: FilterLoggerConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface FilterLoggerConfigInterface extends LoggerConfigInterface
{
    public LogLevel $minimum {
        get;
    }

    public LoggerConfigInterface $logger {
        get;
    }
}
