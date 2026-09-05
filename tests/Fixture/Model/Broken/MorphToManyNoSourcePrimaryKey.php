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

use Fixture\Model\Polymorphic\PolyTag;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'morph_to_many_no_pk')]
class MorphToManyNoSourcePrimaryKey
{
    #[Varchar(length: 100)]
    public string $slug = '';

    /**
     * @var Relation<PolyTag>|null
     */
    #[MorphToMany(
        related: PolyTag::class,
        table: 'no_pk_pivot',
        typeColumn: 'taggable_type',
        idColumn: 'taggable_id',
        foreignKey: 'tag_id',
    )]
    public ?Relation $tags = null;
}
