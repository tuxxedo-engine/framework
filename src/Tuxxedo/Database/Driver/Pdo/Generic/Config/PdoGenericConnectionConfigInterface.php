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

namespace Tuxxedo\Database\Driver\Pdo\Generic\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Database\Config\ConnectionConfigInterface;

#[DefaultImplementation(class: PdoGenericConnectionConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface PdoGenericConnectionConfigInterface extends ConnectionConfigInterface
{
    public string $dsn {
        get;
    }

    public string $username {
        get;
    }

    public string $password {
        get;
    }

    public bool $persistent {
        get;
    }

    public bool $lazy {
        get;
    }
}
