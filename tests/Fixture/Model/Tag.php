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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Identifier;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'tags')]
class Tag
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Identifier]
    #[Varchar(length: 100)]
    public string $slug = '';

    #[Varchar(length: 100)]
    public string $name = '';

    #[Char(length: 3)]
    public string $category = '';

    /**
     * @var Relation<Post>|null
     */
    #[BelongsToMany(
        related: Post::class,
        table: 'post_tag',
        localKey: 'tag_id',
        foreignKey: 'post_id',
    )]
    public ?Relation $posts = null;
}
