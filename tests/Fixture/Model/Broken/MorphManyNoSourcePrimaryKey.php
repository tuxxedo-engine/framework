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

use Fixture\Model\Polymorphic\PolyComment;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'morph_many_no_pk')]
class MorphManyNoSourcePrimaryKey
{
    #[Varchar(length: 100)]
    public string $slug = '';

    /**
     * @var Relation<PolyComment>|null
     */
    #[MorphMany(
        related: PolyComment::class,
        typeColumn: 'commentable_type',
        idColumn: 'commentable_id',
    )]
    public ?Relation $comments = null;
}
