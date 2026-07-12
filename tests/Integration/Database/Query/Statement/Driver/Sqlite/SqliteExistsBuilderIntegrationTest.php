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

use Integration\Database\Query\Statement\AbstractExistsBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Sqlite\Config\SqliteConnectionConfig;
use Tuxxedo\Database\Driver\Sqlite\SqliteConnection;

#[RequiresPhpExtension('sqlite3')]
class SqliteExistsBuilderIntegrationTest extends AbstractExistsBuilderIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return SqliteConnection::create(
            container: $container,
            config: new SqliteConnectionConfig(
                name: 'test',
                database: ':memory:',
            ),
        );
    }
}
