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

namespace Integration\Model\Sqlite;

use Integration\Model\AbstractAggregateCrossConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

#[RequiresPhpExtension('sqlite3')]
class AggregateCrossConnectionIntegrationTest extends AbstractAggregateCrossConnectionIntegrationTestCase
{
    protected function createPrimaryConnection(): ConnectionInterface
    {
        return SqliteConnection::create(
            container: $this->container,
            config: new SqliteConnectionConfig(
                name: 'primary',
                role: ConnectionRole::DEFAULT,
                database: ':memory:',
            ),
        );
    }

    protected function createAuditConnection(): ConnectionInterface
    {
        return SqliteConnection::create(
            container: $this->container,
            config: new SqliteConnectionConfig(
                name: 'audit',
                role: ConnectionRole::NONE,
                database: ':memory:',
            ),
        );
    }
}
