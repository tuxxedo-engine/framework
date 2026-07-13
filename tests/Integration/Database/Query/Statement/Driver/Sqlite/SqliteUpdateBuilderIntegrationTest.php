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

namespace Integration\Database\Query\Statement\Driver\Sqlite;

use Integration\Database\Query\Statement\AbstractUpdateBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\SqliteConnectionFactory;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('sqlite3')]
class SqliteUpdateBuilderIntegrationTest extends AbstractUpdateBuilderIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        return SqliteConnectionFactory::create();
    }
}
