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
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;

#[Table(name: 'soft_delete_cascade_mismatch')]
class SoftDeleteCascadeMismatch
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[DeletedAt]
    public ?\DateTimeImmutable $deletedAt = null;

    #[HasOne(
        related: ValidTarget::class,
        foreignKey: 'owner_id',
        onDelete: CascadeAction::CASCADE,
    )]
    public ?ValidTarget $target = null;
}
