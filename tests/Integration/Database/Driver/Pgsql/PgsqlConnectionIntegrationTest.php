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

namespace Integration\Database\Driver\Pgsql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PgsqlConnectionFactory;
use Support\Database\RealDatabaseIntegrationSetup;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pgsql')]
class PgsqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return PgsqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function createUsersSchema(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE users (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, email VARCHAR(255) NULL)',
            native: true,
        );
    }
}
