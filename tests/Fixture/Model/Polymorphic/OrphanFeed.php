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
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'orphan_feeds')]
class OrphanFeed
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    /**
     * @var Relation<PolyComment>|null
     */
    #[MorphMany(
        related: PolyComment::class,
        typeColumn: 'commentable_type',
        idColumn: 'commentable_id',
        onSave: CascadeAction::CASCADE,
        removeOrphan: true,
    )]
    public ?Relation $comments = null;
}
