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
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\LogFailureMode;

#[DefaultImplementation(class: StreamLoggerConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface StreamLoggerConfigInterface extends LoggerConfigInterface
{
    public string $file {
        get;
    }

    public bool $append {
        get;
    }

    public bool $autoFlush {
        get;
    }

    public LogFormat $format {
        get;
    }

    public LogFailureMode $unencodable {
        get;
    }
}
