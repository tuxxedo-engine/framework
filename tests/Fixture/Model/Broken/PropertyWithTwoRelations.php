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
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'property_with_two_relations')]
class PropertyWithTwoRelations
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[HasOne(related: ValidTarget::class, foreignKey: 'owner_id')]
    #[HasMany(related: ValidTarget::class, foreignKey: 'owner_id')]
    public ?ValidTarget $conflicted = null;
}
