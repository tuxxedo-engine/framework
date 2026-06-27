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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Config\ConnectionConfigInterface;

#[DefaultImplementation(class: PdoPgsqlConnectionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface PdoPgsqlConnectionConfigInterface extends ConnectionConfigInterface
{
    public string $dsn {
        get;
    }

    public string $host {
        get;
    }

    public ?int $port {
        get;
    }

    public string $username {
        get;
    }

    public string $password {
        get;
    }

    public string $database {
        get;
    }

    public string $charset {
        get;
    }

    public bool $persistent {
        get;
    }

    public bool $lazy {
        get;
    }

    public ?int $timeout {
        get;
    }

    public bool $sslEnabled {
        get;
    }

    public string $sslMode {
        get;
    }

    public string $sslCa {
        get;
    }

    public string $sslCert {
        get;
    }

    public string $sslKey {
        get;
    }
}
