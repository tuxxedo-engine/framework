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
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'relation_property_type_mismatch')]
class RelationPropertyTypeMismatch
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[HasOne(related: ValidTarget::class, foreignKey: 'owner_id')]
    public ?NoTable $target = null;
}
