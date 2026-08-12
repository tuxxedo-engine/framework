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

namespace Integration\Database\Query\Statement;

abstract class AbstractListDatabasesBuilderIntegrationTestCase extends AbstractBuilderIntegrationTestCase
{
    public function testListDatabasesReturnsNonEmptyList(): void
    {
        $databases = $this->connection->listDatabases()->all();

        self::assertNotEmpty($databases);

        foreach ($databases as $database) {
            self::assertNotSame('', $database);
        }
    }
}
