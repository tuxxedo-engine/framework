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

namespace Tuxxedo\Model\MetaData;

use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Attribute\PrimaryKey;

interface ModelMetaDataInterface
{
    /**
     * @var class-string
     */
    public string $model {
        get;
    }

    public string $table {
        get;
    }

    /**
     * @var non-empty-array<ColumnInterface>
     */
    public array $columns {
        get;
    }

    public ?PrimaryKey $primaryKey {
        get;
    }

    // @todo Implement primary keys
    // @todo Implement composite keys
    // @todo Implement relations
    // @todo Implement identifiers
}
