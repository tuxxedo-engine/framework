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

namespace Fixture\Model;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'strict_through_owners')]
class StrictThroughOwner
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'nullable_ref_id')]
    public ?int $nullableRefId = null;

    #[HasOneThrough(
        related: Warehouse::class,
        through: Branch::class,
        firstKey: 'region_id',
        secondKey: 'id',
        localKey: 'nullable_ref_id',
        secondLocalKey: 'warehouse_id',
    )]
    public Warehouse $strictWarehouse;
}
