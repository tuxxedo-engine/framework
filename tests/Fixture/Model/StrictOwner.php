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
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;

#[Table(name: 'strict_owners')]
class StrictOwner
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    #[HasOne(
        related: StrictProfile::class,
        foreignKey: 'owner_id',
        onSave: CascadeAction::CASCADE,
    )]
    public StrictProfile $profile;
}
