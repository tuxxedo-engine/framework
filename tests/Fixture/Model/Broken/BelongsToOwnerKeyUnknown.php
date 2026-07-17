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
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'belongs_to_owner_key_unknown')]
class BelongsToOwnerKeyUnknown
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'target_id')]
    public int $targetId = 0;

    #[BelongsTo(
        related: ValidTarget::class,
        foreignKey: 'target_id',
        ownerKey: 'nonexistent_owner',
    )]
    public ?ValidTarget $target = null;
}
