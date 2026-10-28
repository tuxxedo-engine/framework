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

namespace Integration\Database\Query\Statement\Driver\Pdo\Sqlite;

use Integration\Database\Query\Statement\AbstractBuilderIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\PdoSqliteConnectionFactory;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\SqlException;

#[RequiresPhpExtension('pdo_sqlite')]
class PdoSqliteFullTextBuilderIntegrationTest extends AbstractBuilderIntegrationTestCase
{
    protected function createConnection(): ConnectionInterface
    {
        return PdoSqliteConnectionFactory::create();
    }

    public function testFullTextIndexIsUnsupported(): void
    {
        $table = $this->connection->createTable(
            table: 'articles',
        );

        $table->integer(
            name: 'id',
            primaryKey: true,
        );

        $table->text(
            name: 'search_text',
        );

        $table->fullTextIndex('search_text');

        try {
            $table->execute();

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('does not support full-text search', $exception->getMessage());
        }
    }

    public function testWhereFullTextIsUnsupported(): void
    {
        $this->createUsersSchema();

        try {
            $this->connection->select(
                table: 'users',
            )
                ->whereFullText(
                    columns: [
                        'name',
                    ],
                    search: 'alice',
                );

            self::fail('Expected SqlException');
        } catch (SqlException $exception) {
            self::assertStringContainsString('does not support full-text search', $exception->getMessage());
        }
    }
}
