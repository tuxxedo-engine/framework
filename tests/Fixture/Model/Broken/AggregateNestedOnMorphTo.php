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

use Fixture\Model\Aggregate\AggregateNote;
use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'aggregate_nested_on_morph_to')]
class AggregateNestedOnMorphTo
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<AggregateNote>|null
     */
    #[MorphMany(
        related: AggregateNote::class,
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?Relation $notes = null;

    #[CountAggregate(relation: 'notes.host')]
    public ?int $hostsCount = null;
}
