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
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'nullable_through_owners')]
class NullableThroughOwner
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
    public ?Warehouse $primaryWarehouse = null;

    /**
     * @var Relation<Warehouse>|null
     */
    #[HasManyThrough(
        related: Warehouse::class,
        through: Branch::class,
        firstKey: 'region_id',
        secondKey: 'id',
        localKey: 'nullable_ref_id',
        secondLocalKey: 'warehouse_id',
    )]
    public ?Relation $warehouses = null;

    #[HasOne(
        related: Branch::class,
        foreignKey: 'region_id',
        localKey: 'nullable_ref_id',
    )]
    public ?Branch $firstBranch = null;

    /**
     * @var Relation<Branch>|null
     */
    #[HasMany(
        related: Branch::class,
        foreignKey: 'region_id',
        localKey: 'nullable_ref_id',
    )]
    public ?Relation $branches = null;
}
