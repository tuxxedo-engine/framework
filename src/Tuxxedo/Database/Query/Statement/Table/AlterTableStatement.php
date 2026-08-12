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
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Parser\StatementParserResult;
use Tuxxedo\Database\Query\Parser\StatementParserResultInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\AddUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\ChangeColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropForeignKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropIndex;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropPrimaryKey;
use Tuxxedo\Database\Query\Statement\Table\Operation\DropUnique;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameColumn;
use Tuxxedo\Database\Query\Statement\Table\Operation\RenameTable;

class AlterTableStatement implements AlterTableStatementInterface
{
    /**
     * @var list<AlterOperationInterface>
     */
    public private(set) array $operations = [];

    public function __construct(
        public readonly string $table,
        public readonly ?ConnectionInterface $connection = null,
    ) {
    }

    public function addColumn(
        ColumnInterface $column,
    ): static {
        $this->operations[] = new AddColumn(
            column: $column,
        );

        return $this;
    }

    public function dropColumn(
        string $name,
        bool $ifExists = false,
    ): static {
        $this->operations[] = new DropColumn(
            name: $name,
            ifExists: $ifExists,
        );

        return $this;
    }

    public function renameColumn(
        string $from,
        string $to,
    ): static {
        $this->operations[] = new RenameColumn(
            from: $from,
            to: $to,
        );

        return $this;
    }

    public function changeColumn(
        ColumnInterface $column,
        ?string $using = null,
    ): static {
        $this->operations[] = new ChangeColumn(
            column: $column,
            using: $using,
        );

        return $this;
    }

    public function renameTable(
        string $newName,
    ): static {
        $this->operations[] = new RenameTable(
            newName: $newName,
        );

        return $this;
    }

    public function addIndex(
        array $columns,
        ?string $name = null,
    ): static {
        $this->operations[] = new AddIndex(
            columns: $columns,
            name: $name,
        );

        return $this;
    }

    public function dropIndex(
        string $name,
    ): static {
        $this->operations[] = new DropIndex(
            name: $name,
        );

        return $this;
    }

    public function addUnique(
        array $columns,
        ?string $name = null,
    ): static {
        $this->operations[] = new AddUnique(
            columns: $columns,
            name: $name,
        );

        return $this;
    }

    public function dropUnique(
        string $name,
    ): static {
        $this->operations[] = new DropUnique(
            name: $name,
        );

        return $this;
    }

    public function addForeignKey(
        array $columns,
        string $referencedTable,
        array $referencedColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
        ?string $name = null,
    ): static {
        $this->operations[] = new AddForeignKey(
            columns: $columns,
            referencedTable: $referencedTable,
            referencedColumns: $referencedColumns,
            onDelete: $onDelete,
            onUpdate: $onUpdate,
            name: $name,
        );

        return $this;
    }

    public function dropForeignKey(
        string $name,
    ): static {
        $this->operations[] = new DropForeignKey(
            name: $name,
        );

        return $this;
    }

    public function addPrimaryKey(
        array $columns,
    ): static {
        $this->operations[] = new AddPrimaryKey(
            columns: $columns,
        );

        return $this;
    }

    public function dropPrimaryKey(): static
    {
        $this->operations[] = new DropPrimaryKey();

        return $this;
    }

    public function compile(
        ?ConnectionInterface $connection = null,
    ): StatementParserResultInterface {
        $resolvedConnection = $this->resolveConnection($connection);

        return new StatementParserResult(
            sql: \implode(
                ";\n",
                $this->generateStatements($resolvedConnection->dialect),
            ),
        );
    }

    public function execute(
        ?ConnectionInterface $connection = null,
    ): ResultSetInterface {
        $resolvedConnection = $this->resolveConnection($connection);
        $statements = $this->generateStatements($resolvedConnection->dialect);

        $lastResult = null;

        foreach ($statements as $sql) {
            $lastResult = $resolvedConnection->query(
                sql: $sql,
                native: true,
            );
        }

        if ($lastResult === null) {
            $lastResult = $resolvedConnection->query(
                sql: 'SELECT 1',
                native: true,
            );
        }

        return $lastResult;
    }

    /**
     * @return list<string>
     */
    public function generateStatements(
        DialectInterface $dialect,
    ): array {
        return $dialect->compileAlterTable(
            table: $this->table,
            operations: $this->operations,
        );
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
