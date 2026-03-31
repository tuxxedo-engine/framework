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

namespace Tuxxedo\Logger;

use Tuxxedo\Container\DefaultImplementation;

#[DefaultImplementation(class: FileLogger::class)]
interface FileLoggerInterface extends LoggerInterface
{
    public string $file {
        get;
    }

    public bool $autoFlush {
        get;
    }

    public bool $append {
        get;
    }
}
