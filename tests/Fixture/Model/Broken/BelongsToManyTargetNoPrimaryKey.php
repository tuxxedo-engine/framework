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

#[Table(name: 'belongs_to_many_target_no_pk')]
class BelongsToManyTargetNoPrimaryKey
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<TargetWithoutPrimaryKey>|null
     */
    #[BelongsToMany(
        related: TargetWithoutPrimaryKey::class,
        table: 'no_pk_target_pivot',
        localKey: 'source_id',
        foreignKey: 'target_id',
    )]
    public ?Relation $items = null;
}
