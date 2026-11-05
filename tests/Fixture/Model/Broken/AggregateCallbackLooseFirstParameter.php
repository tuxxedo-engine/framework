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
use Tuxxedo\Model\RelationSubqueryInterface;

#[Table(name: 'aggregate_callback_loose_first_parameter')]
class AggregateCallbackLooseFirstParameter
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<AggregateValidTarget>|null
     */
    #[HasMany(related: AggregateValidTarget::class, foreignKey: 'owner_id')]
    public ?Relation $items = null;

    #[CountAggregate(
        relation: 'items',
        callback: static function ($items): void {
        },
    )]
    public ?int $untypedCount = null;

    #[CountAggregate(
        relation: 'items',
        callback: static function (mixed $items): void {
        },
    )]
    public ?int $mixedCount = null;

    #[CountAggregate(
        relation: 'items',
        callback: static function (RelationSubqueryInterface|int $items): void {
        },
    )]
    public ?int $unionCount = null;
}
