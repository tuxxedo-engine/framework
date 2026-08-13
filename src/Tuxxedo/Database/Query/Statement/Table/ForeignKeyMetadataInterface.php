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

interface ForeignKeyMetadataInterface
{
    public string $name {
        get;
    }

    /**
     * @var list<string>
     */
    public array $columns {
        get;
    }

    public string $referencedTable {
        get;
    }

    /**
     * @var list<string>
     */
    public array $referencedColumns {
        get;
    }

    public ForeignKeyAction $onUpdate {
        get;
    }

    public ForeignKeyAction $onDelete {
        get;
    }
}
