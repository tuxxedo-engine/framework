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

namespace Tuxxedo\Database\Query\Statement\Table\Column;

use Tuxxedo\Database\Query\Dialect\DialectInterface;

abstract class AbstractColumn implements ColumnInterface
{
    public function __construct(
        public readonly string $name,
        public readonly bool $nullable = false,
        public readonly bool $primaryKey = false,
        public readonly bool $autoIncrement = false,
        public readonly bool $unique = false,
        public readonly string|int|float|bool|null $default = null,
    ) {
    }

    abstract protected function renderType(
        DialectInterface $dialect,
    ): string;

    public function toSql(
        DialectInterface $dialect,
    ): string {
        $parts = [
            $dialect->identifier($this->name),
            $this->renderType($dialect),
        ];

        if (!$this->nullable) {
            $parts[] = 'NOT NULL';
        }

        if ($this->default !== null) {
            $parts[] = 'DEFAULT ' . $this->renderDefaultValue($this->default);
        }

        if ($this->autoIncrement) {
            $parts[] = $dialect->autoIncrementClause();
        } elseif ($this->primaryKey) {
            $parts[] = 'PRIMARY KEY';
        }

        if ($this->unique && !$this->primaryKey && !$this->autoIncrement) {
            $parts[] = 'UNIQUE';
        }

        return \join(' ', $parts);
    }

    private function renderDefaultValue(
        string|int|float|bool $default,
    ): string {
        if (\is_bool($default)) {
            return $default
                ? 'TRUE'
                : 'FALSE';
        }

        if (\is_int($default) || \is_float($default)) {
            return (string) $default;
        }

        return "'" . \str_replace("'", "''", $default) . "'";
    }
}
