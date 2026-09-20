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

use Fixture\Model\User;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'composite_child_of_single_parent')]
#[CompositeKey('scope', 'name')]
class CompositeChildOfSingleParent
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    #[Integer(name: 'user_id')]
    public int $userId = 0;

    #[Varchar(length: 255)]
    public string $label = '';

    #[BelongsTo(
        related: User::class,
        foreignKey: 'user_id',
    )]
    public ?User $user = null;
}
