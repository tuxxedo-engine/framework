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

class SqlException extends \Exception
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

    public static function fromCannotEscapeIdentifier(
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot escape identifier, as its invalid: %s',
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
                'Bulk insertions must be the same sized array, expected %d but got %d',
                $expectedRows,
                $rows,
            ),
        );
    }

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
}
