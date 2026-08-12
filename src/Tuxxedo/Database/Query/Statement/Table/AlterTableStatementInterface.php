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

use Tuxxedo\Database\Query\Statement\StatementInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;

interface AlterTableStatementInterface extends StatementInterface
{
    /**
     * @var list<AlterOperationInterface>
     */
    public array $operations {
        get;
    }

    public function dropColumn(
        string $name,
        bool $ifExists = false,
    ): static;

    public function addColumn(
        ColumnInterface $column,
    ): static;

    public function renameColumn(
        string $from,
        string $to,
    ): static;

    public function changeColumn(
        ColumnInterface $column,
        ?string $using = null,
    ): static;

    public function renameTable(
        string $newName,
    ): static;

    /**
     * @param list<string> $columns
     */
    public function addIndex(
        array $columns,
        ?string $name = null,
    ): static;

    public function dropIndex(
        string $name,
    ): static;

    /**
     * @param list<string> $columns
     */
    public function addUnique(
        array $columns,
        ?string $name = null,
    ): static;

    public function dropUnique(
        string $name,
    ): static;

    /**
     * @param list<string> $columns
     * @param list<string> $referencedColumns
     */
    public function addForeignKey(
        array $columns,
        string $referencedTable,
        array $referencedColumns,
        ?ForeignKeyAction $onDelete = null,
        ?ForeignKeyAction $onUpdate = null,
        ?string $name = null,
    ): static;

    public function dropForeignKey(
        string $name,
    ): static;

    /**
     * @param list<string> $columns
     */
    public function addPrimaryKey(
        array $columns,
    ): static;

    public function dropPrimaryKey(): static;
}
