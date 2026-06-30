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

class CharColumn extends AbstractColumn
{
    public function __construct(
        string $name,
        public readonly int $length,
        bool $nullable = false,
        bool $primaryKey = false,
        bool $unique = false,
        string|null $default = null,
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
            'CHAR(%d)',
            $this->length,
        );
    }
}
