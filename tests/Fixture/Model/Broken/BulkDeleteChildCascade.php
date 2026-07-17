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
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'bulk_delete_child_cascade')]
class BulkDeleteChildCascade
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    /**
     * @var Relation<ChildWithCascadeRelation>|null
     */
    #[HasMany(
        related: ChildWithCascadeRelation::class,
        foreignKey: 'owner_id',
        onDelete: CascadeAction::CASCADE,
        bulkDelete: true,
    )]
    public ?Relation $items = null;
}
