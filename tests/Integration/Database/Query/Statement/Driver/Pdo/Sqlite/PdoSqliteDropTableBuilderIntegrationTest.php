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

namespace Integration\Database\Query\Statement\Driver\Pdo\Sqlite;

use Integration\Database\Query\Statement\AbstractDropTableBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tuxxedo\Container\Container;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pdo\Sqlite\Config\PdoSqliteConnectionConfig;
use Tuxxedo\Database\Driver\Pdo\Sqlite\PdoSqliteConnection;

#[RequiresPhpExtension('pdo_sqlite')]
class PdoSqliteDropTableBuilderIntegrationTest extends AbstractDropTableBuilderIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        $container = new Container();
        $container->singleton(
            class: $container,
        );

        return PdoSqliteConnection::create(
            container: $container,
            config: new PdoSqliteConnectionConfig(
                name: 'test',
                database: ':memory:',
            ),
        );
    }
}
