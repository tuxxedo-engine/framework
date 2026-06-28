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

namespace Tuxxedo\Database\Driver\Pdo\Sqlite\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;

#[DefaultImplementation(class: PdoSqliteConnectionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface PdoSqliteConnectionConfigInterface extends PdoConnectionConfigInterface
{
    public string $database {
        get;
    }

    public ?int $timeout {
        get;
    }
}
