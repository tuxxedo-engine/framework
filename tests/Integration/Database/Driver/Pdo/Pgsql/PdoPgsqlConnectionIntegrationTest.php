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

namespace Integration\Database\Driver\Pdo\Pgsql;

use Integration\Database\AbstractConnectionIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PdoPgsqlConnectionFactory;
use Support\Database\PgsqlSchemaProvider;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Database\ConnectionRole;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pdo_pgsql')]
class PdoPgsqlConnectionIntegrationTest extends AbstractConnectionIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createConnection(
        ConnectionRole $role = ConnectionRole::DEFAULT,
    ): ConnectionInterface {
        return PdoPgsqlConnectionFactory::create(
            role: $role,
        );
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new PgsqlSchemaProvider();
    }
}
