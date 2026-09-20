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

namespace Fixture\Model\Broken;

use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'aggregate_non_nullable_slot')]
class AggregateNonNullableSlot
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<AggregateValidTarget>|null
     */
    #[HasMany(related: AggregateValidTarget::class, foreignKey: 'owner_id')]
    public ?Relation $items = null;

    #[CountAggregate(relation: 'items')]
    public int $itemsCount = 0;
}
