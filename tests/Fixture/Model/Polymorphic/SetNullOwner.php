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

namespace Fixture\Model\Polymorphic;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'set_null_owners')]
class SetNullOwner
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    #[MorphOne(
        related: NullableAvatar::class,
        typeColumn: 'subject_type',
        idColumn: 'subject_id',
        onDelete: CascadeAction::SET_NULL,
    )]
    public ?NullableAvatar $avatar = null;

    /**
     * @var Relation<NullableAvatar>|null
     */
    #[MorphMany(
        related: NullableAvatar::class,
        typeColumn: 'subject_type',
        idColumn: 'subject_id',
        onDelete: CascadeAction::SET_NULL,
    )]
    public ?Relation $gallery = null;
}
