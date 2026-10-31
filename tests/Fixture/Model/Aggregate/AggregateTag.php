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

namespace Fixture\Model\Aggregate;

use Tuxxedo\Model\Attribute\Aggregate\SumAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'aggregate_tags')]
class AggregateTag
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 64)]
    public string $label = '';

    /**
     * @var Relation<AggregateOwner>|null
     */
    #[BelongsToMany(
        related: AggregateOwner::class,
        table: 'aggregate_owner_tag',
        localKey: 'tag_id',
        foreignKey: 'owner_id',
    )]
    public ?Relation $owners = null;

    #[SumAggregate(relation: 'owners.orders', column: 'amount')]
    public ?int $ownerOrdersTotal = null;
}
