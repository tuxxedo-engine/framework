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

use Tuxxedo\Database\Query\Statement\Condition\ConditionOperator;
use Tuxxedo\Model\Attribute\Aggregate\AvgAggregate;
use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MaxAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MinAggregate;
use Tuxxedo\Model\Attribute\Aggregate\SumAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;
use Tuxxedo\Model\RelationSubqueryInterface;

#[Table(name: 'aggregate_owners')]
class AggregateOwner
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'region_id')]
    public ?int $regionId = null;

    #[Varchar(length: 64)]
    public string $label = '';

    #[BelongsTo(related: AggregateRegion::class, foreignKey: 'region_id')]
    public ?AggregateRegion $region = null;

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

    #[CountAggregate(relation: 'region.orders')]
    public ?int $regionOrdersCount = null;

    #[CountAggregate(relation: 'orders.owner')]
    public ?int $orderOwnersCount = null;

    #[CountAggregate(
        relation: 'orders',
        callback: static function (RelationSubqueryInterface $orders): void {
            $orders->where('aggregate_orders.amount', 20, ConditionOperator::GREATER_THAN_OR_EQUAL);
        },
    )]
    public ?int $largeOrdersCount = null;

    #[SumAggregate(
        relation: 'orders',
        column: 'amount',
        callback: self::amountAtLeast(...),
    )]
    public ?int $ordersTotalAtLeast = null;

    #[MaxAggregate(
        relation: 'orders',
        column: 'amount',
        callback: self::amountBelow(...),
    )]
    public ?int $ordersMaxBelow = null;

    #[AvgAggregate(
        relation: 'orders',
        column: 'amount',
        callback: static function (RelationSubqueryInterface $orders, int $amount, int ...$amounts): void {
            $orders->whereIn(
                'aggregate_orders.amount',
                [
                    $amount,
                    ...\array_values($amounts),
                ],
            );
        },
    )]
    public ?float $ordersAverageOf = null;

    public static function amountAtLeast(
        RelationSubqueryInterface $orders,
        int $minimum,
    ): void {
        $orders->where('aggregate_orders.amount', $minimum, ConditionOperator::GREATER_THAN_OR_EQUAL);
    }

    public static function amountBelow(
        RelationSubqueryInterface $orders,
        int $limit = 100,
    ): void {
        $orders->where('aggregate_orders.amount', $limit, ConditionOperator::LESS_THAN);
    }
}
