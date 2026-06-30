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

class RawColumn extends AbstractColumn
{
    public function __construct(
        string $name,
        public readonly string $sql,
        bool $nullable = false,
    ) {
        parent::__construct(
            name: $name,
            nullable: $nullable,
        );
    }

    protected function renderType(
        DialectInterface $dialect,
    ): string {
        return $this->sql;
    }
}
