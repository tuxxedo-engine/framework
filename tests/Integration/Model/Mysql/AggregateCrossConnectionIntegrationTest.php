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

namespace Integration\Model\Mysql;

use Integration\Model\AbstractAggregateCrossConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlTestEnv;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Mysql\Config\MysqlConnectionConfig;
use Tuxxedo\Database\Driver\Mysql\MysqlConnection;

#[RequiresPhpExtension('mysqli')]
class AggregateCrossConnectionIntegrationTest extends AbstractAggregateCrossConnectionIntegrationTestCase
{
    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::mysqlUnavailableReason();
    }

    protected function createPrimaryConnection(): ConnectionInterface
    {
        return $this->buildConnection(
            name: 'primary',
            role: ConnectionRole::DEFAULT,
        );
    }

    protected function createAuditConnection(): ConnectionInterface
    {
        return $this->buildConnection(
            name: 'audit',
            role: ConnectionRole::NONE,
        );
    }

    private function buildConnection(
        string $name,
        ConnectionRole $role,
    ): ConnectionInterface {
        return MysqlConnection::create(
            container: $this->container,
            config: new MysqlConnectionConfig(
                name: $name,
                role: $role,
                host: MysqlTestEnv::host(),
                port: MysqlTestEnv::port(),
                unixSocket: MysqlTestEnv::socket(),
                username: MysqlTestEnv::username(),
                password: MysqlTestEnv::password(),
                database: MysqlTestEnv::databaseName(),
                charset: MysqlTestEnv::charset(),
                timeout: MysqlTestEnv::timeout(),
            ),
        );
    }
}
