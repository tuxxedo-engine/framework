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

namespace Tuxxedo\Database\Query\Dialect\Sqlite;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\NativeColumnTypeParserInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DateTimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TinyIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescriptionInterface;

class SqliteNativeColumnTypeParser implements NativeColumnTypeParserInterface
{
    private const string DRIVER = 'sqlite';

    public function parse(
        ColumnDescriptionInterface $description,
    ): ColumnInterface {
        $normalized = \strtolower(\trim($description->nativeType));
        $baseType = $this->extractBaseType($normalized);
        $arguments = $this->extractArguments($normalized);

        return match ($baseType) {
            'integer', 'int' => new IntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'tinyint' => new TinyIntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'smallint' => new SmallIntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'bigint' => new BigIntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'real', 'float', 'double' => new DoubleColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                default: $this->coerceFloatDefault($description->default),
            ),
            'numeric', 'decimal' => $this->parseDecimal($description, $arguments),
            'varchar' => $this->parseVarchar($description, $arguments),
            'char' => $this->parseChar($description, $arguments),
            'text' => new TextColumn(
                name: $description->name,
                nullable: $description->nullable,
            ),
            'blob' => new BlobColumn(
                name: $description->name,
                nullable: $description->nullable,
            ),
            'datetime' => new DateTimeColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            'timestamp' => new TimestampColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            'date' => new DateColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            'time' => new TimeColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            default => throw DatabaseException::fromUnknownNativeColumnType(
                nativeType: $description->nativeType,
                driver: self::DRIVER,
            ),
        };
    }

    private function parseDecimal(
        ColumnDescriptionInterface $description,
        string $arguments,
    ): ColumnInterface {
        $parts = \array_map(
            static fn (string $part): string => \trim($part),
            \explode(',', $arguments),
        );

        if (\sizeof($parts) !== 2) {
            throw DatabaseException::fromUnknownNativeColumnType(
                nativeType: $description->nativeType,
                driver: self::DRIVER,
            );
        }

        return new DecimalColumn(
            name: $description->name,
            precision: (int) $parts[0],
            scale: (int) $parts[1],
            nullable: $description->nullable,
            primaryKey: $description->primary,
            default: $this->coerceFloatDefault($description->default),
        );
    }

    private function parseVarchar(
        ColumnDescriptionInterface $description,
        string $arguments,
    ): ColumnInterface {
        if ($arguments === '') {
            throw DatabaseException::fromUnknownNativeColumnType(
                nativeType: $description->nativeType,
                driver: self::DRIVER,
            );
        }

        return new VarcharColumn(
            name: $description->name,
            length: (int) $arguments,
            nullable: $description->nullable,
            primaryKey: $description->primary,
            default: $description->default,
        );
    }

    private function parseChar(
        ColumnDescriptionInterface $description,
        string $arguments,
    ): ColumnInterface {
        if ($arguments === '') {
            throw DatabaseException::fromUnknownNativeColumnType(
                nativeType: $description->nativeType,
                driver: self::DRIVER,
            );
        }

        return new CharColumn(
            name: $description->name,
            length: (int) $arguments,
            nullable: $description->nullable,
            primaryKey: $description->primary,
            default: $description->default,
        );
    }

    private function extractBaseType(
        string $normalized,
    ): string {
        $parenPosition = \strpos($normalized, '(');

        if ($parenPosition === false) {
            return \trim($normalized);
        }

        return \trim(\substr($normalized, 0, $parenPosition));
    }

    private function extractArguments(
        string $normalized,
    ): string {
        $openPosition = \strpos($normalized, '(');

        if ($openPosition === false) {
            return '';
        }

        $closePosition = \strrpos($normalized, ')');

        if ($closePosition === false || $closePosition <= $openPosition) {
            return '';
        }

        return \substr(
            string: $normalized,
            offset: $openPosition + 1,
            length: $closePosition - $openPosition - 1,
        );
    }

    private function coerceIntDefault(
        ?string $default,
    ): ?int {
        return $default === null
            ? null
            : (int) $default;
    }

    private function coerceFloatDefault(
        ?string $default,
    ): ?float {
        return $default === null
            ? null
            : (float) $default;
    }
}
