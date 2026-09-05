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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'morph_one_bad_target')]
class MorphOneRelatedNotAModel
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    #[MorphOne(
        related: \stdClass::class,
        typeColumn: 'subject_type',
        idColumn: 'subject_id',
    )]
    public ?\stdClass $avatar = null;
}
