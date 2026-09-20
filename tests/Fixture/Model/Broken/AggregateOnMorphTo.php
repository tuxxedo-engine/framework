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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'aggregate_on_morph_to')]
class AggregateOnMorphTo
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'host_type', length: 64)]
    public string $hostType = '';

    #[Integer(name: 'host_id')]
    public int $hostId = 0;

    #[MorphTo(
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?object $host = null;

    #[CountAggregate(relation: 'host')]
    public ?int $count = null;
}
