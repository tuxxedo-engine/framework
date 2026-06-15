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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatement;
use Tuxxedo\Database\Query\Statement\Table\DropTableStatementInterface;

class Query
{
    public static function insert(
        string $table,
        ?ConnectionInterface $connection = null,
    ): InsertStatementInterface {
        return new InsertStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function insertBulk(
        string $table,
        ?ConnectionInterface $connection = null,
    ): InsertBulkStatementInterface {
        return new InsertBulkStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function dropTable(
        string $table,
        ?ConnectionInterface $connection = null,
    ): DropTableStatementInterface {
        return new DropTableStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function count(
        string $table,
        ?ConnectionInterface $connection = null,
    ): CountStatementInterface {
        return new CountStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function delete(
        string $table,
        ?ConnectionInterface $connection = null,
    ): DeleteStatementInterface {
        return new DeleteStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function exists(
        string $table,
        ?ConnectionInterface $connection = null,
    ): ExistsStatementInterface {
        return new ExistsStatement(
            table: $table,
            connection: $connection,
        );
    }

    public static function update(
        string $table,
        ?ConnectionInterface $connection = null,
    ): UpdateStatementInterface {
        return new UpdateStatement(
            table: $table,
            connection: $connection,
        );
    }
}
