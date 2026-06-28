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

namespace Tuxxedo\Database\Driver\Pdo\Mysql\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;

#[DefaultImplementation(class: PdoMysqlConnectionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface PdoMysqlConnectionConfigInterface extends PdoConnectionConfigInterface
{
    public string $host {
        get;
    }

    public ?int $port {
        get;
    }

    public ?string $unixSocket {
        get;
    }

    public string $database {
        get;
    }

    public string $charset {
        get;
    }

    public ?int $timeout {
        get;
    }

    public bool $sslEnabled {
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

    public bool $sslVerifyPeer {
        get;
    }
}
