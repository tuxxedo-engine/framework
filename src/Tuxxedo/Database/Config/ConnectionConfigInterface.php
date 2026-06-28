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

namespace Tuxxedo\Database\Config;

use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;

interface ConnectionConfigInterface
{
    public string $name {
        get;
    }

    public ConnectionRole $role {
        get;
    }

    /**
     * @var class-string<ConnectionInterface>
     */
    public string $driverClass {
        get;
    }
}
