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

namespace Support\Database;

use Integration\Database\Query\Statement\AbstractDeleteBuilderIntegrationTestCase;

/**
 * @mixin AbstractDeleteBuilderIntegrationTestCase
 */
trait DmlLimitDeleteBuilderTests
{
    public function testDeleteWithLimitAffectsOnlyRequestedRowCount(): void
    {
        $this->createUsersSchema();
        $this->seedUsers();

        $result = $this->connection->delete(
            table: 'users',
        )
            ->limit(1)
            ->execute();

        self::assertSame(
            1,
            $result->affectedRows,
        );

        $remaining = $this->connection->query(
            sql: 'SELECT COUNT(*) AS c FROM users',
            native: true,
        )->fetchAssoc();

        self::assertEquals(
            2,
            $remaining['c'],
        );
    }
}
