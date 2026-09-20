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

namespace Fixture\Model\Composite;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'composite_owner_hasone_children')]
class CompositeOwnerHasOneChild
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'owner_scope', length: 64)]
    public string $ownerScope = '';

    #[Varchar(name: 'owner_name', length: 64)]
    public string $ownerName = '';

    #[Varchar(length: 255)]
    public string $label = '';

    #[BelongsTo(
        related: CompositeOwner::class,
        foreignKey: [
            'owner_scope',
            'owner_name',
        ],
        ownerKey: [
            'scope',
            'name',
        ],
    )]
    public ?CompositeOwner $owner = null;
}
