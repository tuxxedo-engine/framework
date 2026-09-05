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

namespace Integration\Model\Pgsql;

use Integration\Model\AbstractAggregateCrossConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PgsqlTestEnv;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\Pgsql\Config\PgsqlConnectionConfig;
use Tuxxedo\Database\Driver\Pgsql\PgsqlConnection;

#[RequiresPhpExtension('pgsql')]
class AggregateCrossConnectionIntegrationTest extends AbstractAggregateCrossConnectionIntegrationTestCase
{
    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
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
        return PgsqlConnection::create(
            container: $this->container,
            config: new PgsqlConnectionConfig(
                name: $name,
                role: $role,
                host: PgsqlTestEnv::host(),
                port: PgsqlTestEnv::port(),
                unixSocket: PgsqlTestEnv::socket(),
                username: PgsqlTestEnv::username(),
                password: PgsqlTestEnv::password(),
                database: PgsqlTestEnv::databaseName(),
                charset: PgsqlTestEnv::charset(),
                timeout: PgsqlTestEnv::timeout(),
            ),
        );
    }
}
