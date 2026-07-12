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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Driver\ConnectionInterface;

abstract class AbstractBuilderIntegrationTestCase extends TestCase
{
    protected ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createConnection();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    abstract protected function createConnection(): ConnectionInterface;

    protected function createUsersSchema(): void
    {
        $this->connection->query(
            sql: 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT)',
            native: true,
        );
    }

    protected function seedUsers(): void
    {
        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Bob', 'bob@example.test')",
            native: true,
        );

        $this->connection->query(
            sql: "INSERT INTO users (name, email) VALUES ('Charlie', NULL)",
            native: true,
        );
    }
}
