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

use Integration\Model\AbstractValidationIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\PgsqlConnectionFactory;
use Support\Model\ModelSchemaProvider;
use Support\Model\ModelsManagerFactory;
use Support\Model\PgsqlModelSchemaProvider;
use Tuxxedo\Model\ModelsManagerInterface;

#[RequiresPhpExtension('pgsql')]
class ValidationIntegrationTest extends AbstractValidationIntegrationTestCase
{
    protected function realDatabaseSkipReason(): ?string
    {
        return DatabaseServerProbe::pgsqlUnavailableReason();
    }

    protected function createModelsManager(): ModelsManagerInterface
    {
        return ModelsManagerFactory::createFromConnection(
            connection: PgsqlConnectionFactory::create(),
        );
    }

    protected function schemaProvider(): ModelSchemaProvider
    {
        return new PgsqlModelSchemaProvider();
    }
}
