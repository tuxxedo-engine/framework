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
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'through_class_not_a_model')]
class ThroughClassNotAModel
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    /**
     * @var Relation<ValidTarget>|null
     */
    #[HasManyThrough(
        related: ValidTarget::class,
        through: NoTable::class,
        firstKey: 'owner_id',
        secondKey: 'owner_id',
    )]
    public ?Relation $items = null;
}
