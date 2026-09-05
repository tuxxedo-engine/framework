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
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;

#[Table(name: 'employees')]
class Employee
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    #[MorphOne(
        related: Avatar::class,
        typeColumn: 'subject_type',
        idColumn: 'subject_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?Avatar $avatar = null;
}
