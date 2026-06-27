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

namespace Tuxxedo\Database\Driver\Sqlite\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Config\ConnectionConfigInterface;

#[DefaultImplementation(class: SqliteConnectionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface SqliteConnectionConfigInterface extends ConnectionConfigInterface
{
    public string $database {
        get;
    }

    public string $encryptionKey {
        get;
    }

    public ?int $flags {
        get;
    }

    public bool $lazy {
        get;
    }
}
