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

class DecimalColumn extends AbstractColumn
{
    public function __construct(
        string $name,
        public readonly int $precision,
        public readonly int $scale,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        int|float|null $default = null,
    ) {
        parent::__construct(
            name: $name,
            nullable: $nullable,
            primaryKey: $primaryKey,
            unique: $unique,
            default: $default,
        );
    }

    protected function renderType(
        DialectInterface $dialect,
    ): string {
        return $dialect->nativeColumnType($this) ?? \sprintf(
            'DECIMAL(%d, %d)',
            $this->precision,
            $this->scale,
        );
    }
}
