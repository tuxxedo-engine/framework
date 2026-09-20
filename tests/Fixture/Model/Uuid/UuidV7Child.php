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

namespace Fixture\Model\Uuid;

use Tuxxedo\Model\Attribute\Column\Uuid;
use Tuxxedo\Model\Attribute\Column\UuidVersion;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'uuid_v7_children')]
class UuidV7Child
{
    #[Uuid(version: UuidVersion::V7, primaryKey: true)]
    public ?string $id = null;

    #[Uuid(version: UuidVersion::V7, name: 'owner_id')]
    public ?string $ownerId = null;

    #[Varchar(length: 255)]
    public string $label = '';

    #[BelongsTo(
        related: UuidV7Owner::class,
        foreignKey: 'owner_id',
    )]
    public ?UuidV7Owner $owner = null;
}
