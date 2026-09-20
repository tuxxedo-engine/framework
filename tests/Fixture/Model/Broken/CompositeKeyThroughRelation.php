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

use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'composite_key_through_relation')]
#[CompositeKey('scope', 'name')]
class CompositeKeyThroughRelation
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    #[HasOneThrough(
        related: ValidTarget::class,
        through: ValidTarget::class,
        firstKey: 'owner_id',
        secondKey: 'id',
    )]
    public ?ValidTarget $item = null;
}
