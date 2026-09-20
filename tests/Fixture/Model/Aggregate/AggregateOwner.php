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

use Tuxxedo\Model\Attribute\Aggregate\AvgAggregate;
use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MaxAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MinAggregate;
use Tuxxedo\Model\Attribute\Aggregate\SumAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'aggregate_owners')]
class AggregateOwner
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 64)]
    public string $label = '';

    /**
     * @var Relation<AggregateOrder>|null
     */
    #[HasMany(related: AggregateOrder::class, foreignKey: 'owner_id')]
    public ?Relation $orders = null;

    /**
     * @var Relation<AggregateTag>|null
     */
    #[BelongsToMany(
        related: AggregateTag::class,
        table: 'aggregate_owner_tag',
        localKey: 'owner_id',
        foreignKey: 'tag_id',
    )]
    public ?Relation $tags = null;

    /**
     * @var Relation<AggregateNote>|null
     */
    #[MorphMany(
        related: AggregateNote::class,
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?Relation $notes = null;

    /**
     * @var Relation<AggregatePolyTag>|null
     */
    #[MorphToMany(
        related: AggregatePolyTag::class,
        table: 'aggregate_owner_poly_tag',
        typeColumn: 'owner_type',
        idColumn: 'owner_id',
        foreignKey: [
            'poly_tag_realm',
            'poly_tag_code',
        ],
    )]
    public ?Relation $polyTags = null;

    #[CountAggregate(relation: 'orders')]
    public ?int $ordersCount = null;

    #[SumAggregate(relation: 'orders', column: 'amount')]
    public ?int $ordersTotal = null;

    #[AvgAggregate(relation: 'orders', column: 'amount')]
    public ?float $ordersAverage = null;

    #[MinAggregate(relation: 'orders', column: 'amount')]
    public ?int $ordersMin = null;

    #[MaxAggregate(relation: 'orders', column: 'amount')]
    public ?int $ordersMax = null;

    #[CountAggregate(relation: 'tags')]
    public ?int $tagsCount = null;

    #[CountAggregate(relation: 'notes')]
    public ?int $notesCount = null;

    #[SumAggregate(relation: 'notes', column: 'length')]
    public ?int $notesLengthSum = null;

    #[SumAggregate(relation: 'polyTags', column: 'weight')]
    public ?int $polyTagsWeight = null;
}
