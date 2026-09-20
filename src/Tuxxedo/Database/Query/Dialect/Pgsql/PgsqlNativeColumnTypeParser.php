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

namespace Tuxxedo\Database\Query\Dialect\Pgsql;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\NativeColumnTypeParserInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\BigIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BlobColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\BooleanColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\CharColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\DateColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DecimalColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\DoubleColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\IntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\JsonColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\SmallIntegerColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TextColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimeColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\TimestampColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\UuidColumn;
use Tuxxedo\Database\Query\Statement\Table\Column\VarcharColumn;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescriptionInterface;

class PgsqlNativeColumnTypeParser implements NativeColumnTypeParserInterface
{
    private const string DRIVER = 'pgsql';

    public function parse(
        ColumnDescriptionInterface $description,
    ): ColumnInterface {
        $normalized = \strtolower(\trim($description->nativeType));
        $baseType = $this->extractBaseType($normalized);
        $arguments = $this->extractArguments($normalized);

        return match ($baseType) {
            'smallint', 'int2' => new SmallIntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'integer', 'int', 'int4' => new IntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'bigint', 'int8' => new BigIntegerColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                autoIncrement: $description->autoIncrement,
                default: $this->coerceIntDefault($description->default),
            ),
            'boolean', 'bool' => new BooleanColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $this->coerceBoolDefault($description->default),
            ),
            'real', 'double precision', 'float4', 'float8' => new DoubleColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                default: $this->coerceFloatDefault($description->default),
            ),
            'numeric', 'decimal' => $this->parseDecimal($description, $arguments),
            'character varying', 'varchar' => $this->parseVarchar($description, $arguments),
            'character', 'char', 'bpchar' => $this->parseChar($description, $arguments),
            'text' => new TextColumn(
                name: $description->name,
                nullable: $description->nullable,
            ),
            'bytea' => new BlobColumn(
                name: $description->name,
                nullable: $description->nullable,
            ),
            'json', 'jsonb' => new JsonColumn(
                name: $description->name,
                nullable: $description->nullable,
            ),
            'uuid' => new UuidColumn(
                name: $description->name,
                nullable: $description->nullable,
                primaryKey: $description->primary,
                default: $description->default,
            ),
            'timestamp', 'timestamp without time zone', 'timestamp with time zone', 'timestamptz' => new TimestampColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            'date' => new DateColumn(
                name: $description->name,
                nullable: $description->nullable,
                default: $description->default,
            ),
            'time', 'time without time zone', 'time with time zone', 'timetz' => new TimeColumn(
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

    private function coerceBoolDefault(
        ?string $default,
    ): ?bool {
        return $default === null
            ? null
            : $default === 'true' || $default === 't' || $default === '1';
    }
}
