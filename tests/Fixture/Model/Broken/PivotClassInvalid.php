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

use Fixture\Model\PostStatus;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'pivot_class_invalid')]
class PivotClassInvalid
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<ValidTarget>|null
     */
    #[BelongsToMany(
        related: ValidTarget::class,
        table: 'pivot_links',
        localKey: 'owner_id',
        foreignKey: 'target_id',
        pivot: PostStatus::class,
    )]
    public ?Relation $targets = null;
}
