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
use Tuxxedo\Logger\Formatter\UnencodableValueMode;

#[DefaultImplementation(class: SyslogLoggerConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface SyslogLoggerConfigInterface extends LoggerConfigInterface
{
    public string $ident {
        get;
    }

    public bool $persistent {
        get;
    }

    public int $facility {
        get;
    }

    public int $options {
        get;
    }

    public ?LogFormat $format {
        get;
    }

    public UnencodableValueMode $unencodable {
        get;
    }
}
