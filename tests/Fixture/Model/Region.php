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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'regions')]
class Region
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    #[HasOneThrough(
        related: Warehouse::class,
        through: Branch::class,
        firstKey: 'region_id',
        secondKey: 'id',
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
        secondLocalKey: 'warehouse_id',
    )]
    public ?Relation $warehouses = null;
}
