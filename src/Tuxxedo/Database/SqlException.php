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

namespace Tuxxedo\Database;

use Tuxxedo\Database\Query\FullText\FullText;
use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;
use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Database\Query\Statement\Table\Operation\AlterOperationInterface;
use Tuxxedo\Exception;

class SqlException extends Exception
{
    public static function fromUnboundPlaceholder(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Placeholder named `:%s` is not bound',
                $name,
            ),
        );
    }

    public static function fromPlaceholderArrayInvalidValue(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Placeholder named `:%s[]` must be bound to a non-empty array',
                $name,
            ),
        );
    }

    public static function fromPlaceholderArrayWrongSyntax(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Placeholder named `:%1$s` is bound to an array value, use `:%1$s[]` instead',
                $name,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromCannotEscapeIdentifier(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot escape invalid identifier `%s`',
                $name,
            ),
        );
    }

    public static function fromUnexpectedInsertBulkSize(
        int $rows,
        int $expectedRows,
    ): self {
        return new self(
            message: \sprintf(
                'Bulk insertion column count mismatch: expected %d, got %d',
                $expectedRows,
                $rows,
            ),
        );
    }

    /**
     * @param list<string> $columns
     * @param list<string> $expectedColumns
     */
    public static function fromUnexpectedInsertBulkColumns(
        array $columns,
        array $expectedColumns,
    ): self {
        return new self(
            message: \sprintf(
                'Bulk insertion column shape mismatch: expected [%s], got [%s]',
                \join(', ', $expectedColumns),
                \join(', ', $columns),
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromSubqueryStatementMustExtendAbstractStatement(
        string $actualType,
    ): self {
        return new self(
            message: \sprintf(
                'Subquery statements must extend AbstractStatement, got `%s`',
                $actualType,
            ),
        );
    }

    public static function fromUnknownOperator(
        string $value,
        string $enum,
    ): self {
        return new self(
            message: \sprintf(
                'Unknown operator `%s` for enum `%s`',
                $value,
                $enum,
            ),
        );
    }

    public static function fromUnsupportedSubqueryComparisonOperator(
        ConditionOperator $operator,
    ): self {
        return new self(
            message: \sprintf(
                'Operator `%s` cannot compare a subquery to a value; use a scalar comparison operator',
                $operator->value,
            ),
        );
    }

    public static function fromInvalidJoinCondition(
        string $table,
    ): self {
        return new self(
            message: \sprintf(
                'Join on `%s` needs either `first` and `second` columns or a non-empty `on` callback, not both or neither',
                $table,
            ),
        );
    }

    public static function fromAggregateRequiresColumn(
        AggregateFunction $function,
    ): self {
        return new self(
            message: \sprintf(
                'Aggregate `%s` needs a column; only a plain COUNT may count rows without one',
                $function->value,
            ),
        );
    }

    public static function fromCreateTableWithoutColumns(
        string $table,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot compile CREATE TABLE for `%s`: no columns have been declared',
                $table,
            ),
        );
    }

    /**
     * @param class-string $dialect
     */
    public static function fromFullTextUnsupported(
        string $dialect,
    ): self {
        return new self(
            message: \sprintf(
                'Dialect "%s" does not support full-text search',
                self::formatShortClass($dialect),
            ),
        );
    }

    public static function fromFullTextIndexMissing(
        string $table,
        string $index,
    ): self {
        return new self(
            message: \sprintf(
                'Full-text search on `%s` requires the full-text index `%s`',
                $table,
                $index,
            ),
        );
    }

    /**
     * @param list<string> $columns
     */
    public static function fromFullTextColumnsSpanTables(
        array $columns,
    ): self {
        return new self(
            message: \sprintf(
                'Full-text columns must belong to a single table, got: %s',
                \join(', ', $columns),
            ),
        );
    }

    public static function fromFullTextWithoutColumns(): self
    {
        return new self(
            message: 'Full-text search requires at least one column',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromFullTextTransliteratorUnavailable(): self
    {
        return new self(
            message: \sprintf(
                'Unable to create the "%s" transliterator for full-text normalization',
                FullText::TRANSLITERATION,
            ),
        );
    }

    /**
     * @param class-string $dialect
     * @param class-string<AlterOperationInterface> $operation
     */
    public static function fromUnsupportedAlterOperation(
        string $dialect,
        string $operation,
    ): self {
        return new self(
            message: \sprintf(
                'Dialect "%s" does not support ALTER operation "%s"',
                self::formatShortClass($dialect),
                self::formatShortClass($operation),
            ),
        );
    }
}
