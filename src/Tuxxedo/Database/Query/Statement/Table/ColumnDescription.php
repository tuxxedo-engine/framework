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

use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;

class ColumnDescription implements ColumnDescriptionInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $nativeType,
        public readonly DialectInterface $dialect,
        public readonly bool $nullable,
        public readonly ?string $default,
        public readonly bool $primary,
        public readonly bool $autoIncrement,
    ) {
    }

    public function toColumn(): ColumnInterface
    {
        return $this->dialect->nativeColumnTypeParser()->parse(
            description: $this,
        );
    }
}
