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

use Integration\Model\AbstractHasManyIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlConnectionFactory;
use Support\Model\ModelSchemaProvider;
use Support\Model\ModelsManagerFactory;
use Support\Model\MysqlModelSchemaProvider;
use Tuxxedo\Model\ModelsManagerInterface;

#[RequiresPhpExtension('mysqli')]
class HasManyIntegrationTest extends AbstractHasManyIntegrationTestCase
{
    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::mysqlUnavailableReason();
    }

    protected function createModelsManager(): ModelsManagerInterface
    {
        return ModelsManagerFactory::createFromConnection(
            connection: MysqlConnectionFactory::create(),
        );
    }

    protected function schemaProvider(): ModelSchemaProvider
    {
        return new MysqlModelSchemaProvider();
    }
}
