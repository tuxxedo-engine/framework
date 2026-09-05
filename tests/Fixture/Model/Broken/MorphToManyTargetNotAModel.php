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
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'morph_to_many_bad_target')]
class MorphToManyTargetNotAModel
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    /**
     * @var Relation<\stdClass>|null
     */
    #[MorphToMany(
        related: \stdClass::class,
        table: 'morph_to_many_bad_target_pivot',
        typeColumn: 'taggable_type',
        idColumn: 'taggable_id',
        foreignKey: 'target_id',
    )]
    public ?Relation $items = null;
}
