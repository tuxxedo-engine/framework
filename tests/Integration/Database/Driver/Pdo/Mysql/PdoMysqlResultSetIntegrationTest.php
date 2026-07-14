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

namespace Integration\Database\Driver\Pdo\Mysql;

use Integration\Database\AbstractResultSetIntegrationTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Support\Database\DatabaseServerProbe;
use Support\Database\MysqlSchemaProvider;
use Support\Database\PdoMysqlConnectionFactory;
use Support\Database\RealDatabaseIntegrationSetup;
use Support\Database\SchemaProvider;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[RequiresPhpExtension('pdo_mysql')]
class PdoMysqlResultSetIntegrationTest extends AbstractResultSetIntegrationTestCase
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

    public function testFetchAssocOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchAssoc();
    }

    public function testFetchRowOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchRow();
    }

    public function testFetchObjectOnDmlResultSetThrowsFromEmptyResultSet(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        $this->expectException(DatabaseException::class);

        $result->fetchObject();
    }

    public function testCountOnDmlResultSetReturnsZero(): void
    {
        $result = $this->connection->query(
            sql: "INSERT INTO users (name) VALUES ('Dave')",
            native: true,
        );

        self::assertCount(
            0,
            $result,
        );
    }
}
