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

namespace Fixture\Model;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'strict_children')]
class StrictChild
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'owner_id')]
    public ?int $ownerId = null;

    #[Varchar(length: 255)]
    public string $label = '';

    #[BelongsTo(
        related: StrictOwner::class,
        foreignKey: 'owner_id',
        ownerKey: 'id',
    )]
    public StrictOwner $owner;
}
