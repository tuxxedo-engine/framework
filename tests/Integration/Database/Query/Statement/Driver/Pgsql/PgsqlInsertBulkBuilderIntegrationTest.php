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

namespace Integration\Database\Query\Statement\Driver\Pgsql;

use Integration\Database\Query\Statement\AbstractInsertBulkBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PgsqlConnectionFactory;
use Support\Database\PgsqlSchemaProvider;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pgsql')]
class PgsqlInsertBulkBuilderIntegrationTest extends AbstractInsertBulkBuilderIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createConnection(): ConnectionInterface
    {
        return PgsqlConnectionFactory::create();
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new PgsqlSchemaProvider();
    }
}
