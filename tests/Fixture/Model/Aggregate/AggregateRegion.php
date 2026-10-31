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

use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Aggregate\SumAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'aggregate_regions')]
class AggregateRegion
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 64)]
    public string $label = '';

    /**
     * @var Relation<AggregateOwner>|null
     */
    #[HasMany(related: AggregateOwner::class, foreignKey: 'region_id')]
    public ?Relation $owners = null;

    /**
     * @var Relation<AggregateOrder>|null
     */
    #[HasManyThrough(
        related: AggregateOrder::class,
        through: AggregateOwner::class,
        firstKey: 'region_id',
        secondKey: 'owner_id',
        secondLocalKey: 'id',
    )]
    public ?Relation $orders = null;

    #[CountAggregate(relation: 'owners.orders')]
    public ?int $ordersCount = null;

    #[SumAggregate(relation: 'owners.orders', column: 'amount')]
    public ?int $ordersTotal = null;

    #[CountAggregate(relation: 'orders.owner')]
    public ?int $orderOwnersCount = null;

    #[CountAggregate(relation: 'owners.tags')]
    public ?int $tagsCount = null;

    #[SumAggregate(relation: 'owners.notes', column: 'length')]
    public ?int $notesLength = null;

    #[SumAggregate(relation: 'owners.polyTags', column: 'weight')]
    public ?int $polyTagsWeight = null;
}
