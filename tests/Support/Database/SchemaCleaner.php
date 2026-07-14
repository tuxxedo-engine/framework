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

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Dialect\MysqlDialect;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;

class SchemaCleaner
{
    public static function dropAllTables(
        ConnectionInterface $connection,
    ): void {
        if ($connection->dialect instanceof SqliteDialect) {
            return;
        }

        if ($connection->dialect instanceof MysqlDialect) {
            self::dropAllMysqlTables($connection);

            return;
        }

        // @todo PGSQL branch: query pg_catalog.pg_tables WHERE schemaname = current_schema(), DROP TABLE IF EXISTS ... CASCADE
        throw new \LogicException(
            \sprintf(
                'SchemaCleaner does not yet support dialect %s',
                $connection->dialect::class,
            ),
        );
    }

    private static function dropAllMysqlTables(
        ConnectionInterface $connection,
    ): void {
        $connection->query(
            sql: 'SET FOREIGN_KEY_CHECKS = 0',
            native: true,
        );

        try {
            $rows = $connection->query(
                sql: 'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE()',
                native: true,
            );

            foreach ($rows as $row) {
                /** @var string $tableName */
                $tableName = $row->properties['name'];

                $connection->query(
                    sql: \sprintf(
                        'DROP TABLE IF EXISTS `%s`',
                        \str_replace('`', '``', $tableName),
                    ),
                    native: true,
                );
            }
        } finally {
            $connection->query(
                sql: 'SET FOREIGN_KEY_CHECKS = 1',
                native: true,
            );
        }
    }
}
