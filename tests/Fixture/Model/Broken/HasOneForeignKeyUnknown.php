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

#[Table(name: 'has_one_foreign_key_unknown')]
class HasOneForeignKeyUnknown
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[HasOne(related: ValidTarget::class, foreignKey: 'nonexistent_fk')]
    public ?ValidTarget $target = null;
}
