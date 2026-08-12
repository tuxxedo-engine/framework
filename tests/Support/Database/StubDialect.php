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

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;

class StubDialect implements DialectInterface
{
    /**
     * @var list<string>
     */
    public private(set) array $quotations;

    public ?string $compileAlterTableTable = null;

    /**
     * @var list<AlterOperationInterface>
     */
    public array $compileAlterTableOperations = [];

    /**
     * @var list<string>
     */
    public array $compileAlterTableResult = [];

    /**
     * @param list<string> $quotations
     */
    public function __construct(
        array $quotations = [
            '\'',
            '"',
        ],
    ) {
        $this->quotations = $quotations;
    }

    public function placeholder(
        int $position,
    ): string {
        return '$' . $position;
    }

    public function identifier(
        string $name,
    ): string {
        return '"' . $name . '"';
    }

    public function qualifiedIdentifier(
        string $name,
    ): string {
        return $this->identifier(
            name: $name,
        );
    }

    public function nativeColumnType(
        ColumnInterface $column,
    ): ?string {
        return null;
    }

    public function autoIncrementClause(): string
    {
        return '';
    }

    public function interpretBoolean(
        mixed $value,
    ): bool {
        if (\is_string($value)) {
            return $value === '1';
        }

        return (bool) $value;
    }

    public function compileAlterTable(
        string $table,
        array $operations,
    ): array {
        $this->compileAlterTableTable = $table;
        $this->compileAlterTableOperations = $operations;

        return $this->compileAlterTableResult;
    }
}
