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

use Fixture\Model\User;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'articles')]
class Article
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'user_id')]
    public int $userId = 0;

    #[Varchar(length: 255)]
    public string $title = '';

    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    public ?User $author = null;

    /**
     * @var Relation<PolyComment>|null
     */
    #[MorphMany(
        related: PolyComment::class,
        typeColumn: 'commentable_type',
        idColumn: 'commentable_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?Relation $comments = null;

    /**
     * @var Relation<PolyTag>|null
     */
    #[MorphToMany(
        related: PolyTag::class,
        table: 'poly_taggables',
        typeColumn: 'taggable_type',
        idColumn: 'taggable_id',
        foreignKey: 'tag_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?Relation $tags = null;
}
