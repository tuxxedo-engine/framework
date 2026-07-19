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

use Tuxxedo\Model\Attribute\Column\Char;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Time;
use Tuxxedo\Model\Attribute\Column\TinyInteger;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'roles')]
class Role
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Char(length: 20)]
    public string $key = '';

    #[Varchar(length: 100)]
    public string $label = '';

    #[TinyInteger]
    public int $sortOrder = 0;

    #[Time]
    public ?\DateTimeImmutable $startsAt = null;

    /**
     * @var Relation<User>|null
     */
    #[BelongsToMany(
        related: User::class,
        table: 'user_role',
        localKey: 'role_id',
        foreignKey: 'user_id',
    )]
    public ?Relation $users = null;
}
