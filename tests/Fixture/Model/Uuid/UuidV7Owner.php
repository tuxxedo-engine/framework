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
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'uuid_v7_owners')]
class UuidV7Owner
{
    #[Uuid(version: UuidVersion::V7, primaryKey: true)]
    public ?string $id = null;

    #[Varchar(length: 255)]
    public string $label = '';

    /**
     * @var Relation<UuidV7Child>|null
     */
    #[HasMany(
        related: UuidV7Child::class,
        foreignKey: 'owner_id',
        onSave: CascadeAction::CASCADE,
    )]
    public ?Relation $children = null;
}
