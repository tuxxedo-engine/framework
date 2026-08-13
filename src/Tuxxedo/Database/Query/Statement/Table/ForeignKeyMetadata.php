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

class ForeignKeyMetadata implements ForeignKeyMetadataInterface
{
    /**
     * @param list<string> $columns
     * @param list<string> $referencedColumns
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly string $referencedTable,
        public readonly array $referencedColumns,
        public readonly ForeignKeyAction $onUpdate,
        public readonly ForeignKeyAction $onDelete,
    ) {
    }
}
