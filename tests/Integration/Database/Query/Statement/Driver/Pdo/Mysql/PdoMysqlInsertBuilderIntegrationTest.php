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

namespace Integration\Database\Query\Statement\Driver\Pdo\Mysql;

use Integration\Database\Query\Statement\AbstractInsertBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlSchemaProvider;
use Support\Database\PdoMysqlConnectionFactory;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pdo_mysql')]
class PdoMysqlInsertBuilderIntegrationTest extends AbstractInsertBuilderIntegrationTestCase
{
    use RealDatabaseIntegrationSetup;

    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::mysqlUnavailableReason();
    }

    protected function createConnection(): ConnectionInterface
    {
        return PdoMysqlConnectionFactory::create();
    }

    protected function schemaProvider(): SchemaProvider
    {
        return new MysqlSchemaProvider();
    }
}
