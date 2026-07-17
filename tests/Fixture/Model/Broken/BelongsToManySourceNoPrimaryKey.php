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

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'belongs_to_many_source_no_pk')]
class BelongsToManySourceNoPrimaryKey
{
    #[Integer]
    public int $someColumn = 0;

    /**
     * @var Relation<ValidTarget>|null
     */
    #[BelongsToMany(
        related: ValidTarget::class,
        table: 'no_pk_pivot',
        localKey: 'source_id',
        foreignKey: 'target_id',
    )]
    public ?Relation $items = null;
}
