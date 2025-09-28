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

namespace Tuxxedo\Database\Driver\Pdo\Pgsql;

use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoPgsqlConnection extends AbstractPdoConnection
{
    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO_PGSQL;
    }

    protected function getDsn(): string
    {
        // @todo Implement getDsn() method.
    }
}
