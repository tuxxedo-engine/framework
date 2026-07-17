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
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'has_many_no_primary_key')]
class HasManyNoPrimaryKey
{
    #[Integer]
    public int $someColumn = 0;

    /**
     * @var Relation<ValidTarget>|null
     */
    #[HasMany(related: ValidTarget::class, foreignKey: 'owner_id')]
    public ?Relation $items = null;
}
