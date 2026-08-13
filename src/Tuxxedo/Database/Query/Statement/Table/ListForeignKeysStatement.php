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

namespace Tuxxedo\Database\Query\Statement\Table;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\ResultSetInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;

class ListForeignKeysStatement implements ListForeignKeysStatementInterface
{
    public function __construct(
        public readonly string $table,
        public readonly ?ConnectionInterface $connection = null,
    ) {
    }

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $this->resolveConnection($connection);

        return $resolvedConnection->dialect->listForeignKeys(
            table: $this->table,
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $prepared = $resolvedConnection->dialect->listForeignKeys(
            table: $this->table,
        );

        return $resolvedConnection->query(
            sql: $prepared->sql,
            parameters: $prepared->parameters,
        );
    }

    public function all(
        ?ConnectionInterface $connection = null,
    ): array {
        $result = $this->execute(
            connection: $connection,
        );

        /**
         * @var array<string, array{referencedTable: string, onUpdate: string, onDelete: string, columns: list<string>, referencedColumns: list<string>}> $accumulator
         */
        $accumulator = [];
        $rowCount = \sizeof($result);

        for ($i = 0; $i < $rowCount; $i++) {
            /** @var array{constraint_name: string, column_name: string, referenced_table: string, referenced_column: string, on_update: string, on_delete: string} $row */
            $row = $result->fetchAssoc();

            $name = $row['constraint_name'];

            if (!\array_key_exists($name, $accumulator)) {
                $accumulator[$name] = [
                    'referencedTable' => $row['referenced_table'],
                    'onUpdate' => $row['on_update'],
                    'onDelete' => $row['on_delete'],
                    'columns' => [],
                    'referencedColumns' => [],
                ];
            }

            $accumulator[$name]['columns'][] = $row['column_name'];
            $accumulator[$name]['referencedColumns'][] = $row['referenced_column'];
        }

        $foreignKeys = [];

        foreach ($accumulator as $name => $data) {
            $foreignKeys[] = new ForeignKeyMetadata(
                name: $name,
                columns: $data['columns'],
                referencedTable: $data['referencedTable'],
                referencedColumns: $data['referencedColumns'],
                onUpdate: self::parseAction($data['onUpdate']),
                onDelete: self::parseAction($data['onDelete']),
            );
        }

        return $foreignKeys;
    }

    public function byName(
        ?ConnectionInterface $connection = null,
    ): array {
        $indexed = [];

        foreach ($this->all($connection) as $foreignKey) {
            $indexed[$foreignKey->name] = $foreignKey;
        }

        return $indexed;
    }

    /**
     * @throws DatabaseException
     */
    private static function parseAction(
        string $action,
    ): ForeignKeyAction {
        return match (\strtoupper($action)) {
            'CASCADE' => ForeignKeyAction::CASCADE,
            'SET NULL' => ForeignKeyAction::SET_NULL,
            'RESTRICT' => ForeignKeyAction::RESTRICT,
            'NO ACTION', '' => ForeignKeyAction::NO_ACTION,
            'SET DEFAULT' => ForeignKeyAction::SET_DEFAULT,
            default => throw DatabaseException::fromUnknownForeignKeyAction($action),
        };
    }

    /**
     * @throws DatabaseException
     */
    private function resolveConnection(
        ?ConnectionInterface $connection,
    ): ConnectionInterface {
        $resolved = $connection ?? $this->connection;

        if ($resolved === null) {
            throw DatabaseException::fromNoConnectionAvailable();
        }

        return $resolved;
    }
}
