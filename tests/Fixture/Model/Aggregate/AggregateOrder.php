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
use Tuxxedo\Model\Attribute\Column\DeletedAt;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Temporal\InstantInterface;

#[Table(name: 'aggregate_orders')]
class AggregateOrder
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'owner_id')]
    public int $ownerId = 0;

    #[Integer]
    public int $amount = 0;

    #[DeletedAt(name: 'deleted_at')]
    public ?InstantInterface $deletedAt = null;

    #[BelongsTo(related: AggregateOwner::class, foreignKey: 'owner_id')]
    public ?AggregateOwner $owner = null;

    #[CountAggregate(relation: 'owner.tags')]
    public ?int $ownerTagsCount = null;

    #[CountAggregate(relation: 'owner.orders')]
    public ?int $siblingOrdersCount = null;

    #[SumAggregate(relation: 'owner.notes', column: 'length')]
    public ?int $ownerNotesLength = null;

    #[SumAggregate(relation: 'owner.polyTags', column: 'weight')]
    public ?int $ownerPolyTagsWeight = null;

    #[CountAggregate(relation: 'owner.region.orders')]
    public ?int $regionOrdersCount = null;
}
