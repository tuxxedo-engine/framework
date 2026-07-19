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

use Tuxxedo\Model\Attribute\Column\DeletedAt;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'bulk_delete_child_behavior')]
class BulkDeleteChildBehavior
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[DeletedAt]
    public ?\DateTimeImmutable $deletedAt = null;

    /**
     * @var Relation<ChildWithDeleteBehavior>|null
     */
    #[HasMany(
        related: ChildWithDeleteBehavior::class,
        foreignKey: 'owner_id',
        onDelete: CascadeAction::CASCADE,
        bulkDelete: true,
    )]
    public ?Relation $items = null;
}
