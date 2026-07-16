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

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\SmallInteger;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'categories')]
class Category
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'parent_id')]
    public ?int $parentId = null;

    #[Varchar(length: 100)]
    public string $name = '';

    #[SmallInteger]
    public int $depth = 0;

    #[BelongsTo(related: Category::class, foreignKey: 'parent_id')]
    public ?Category $parent = null;

    /**
     * @var Relation<Category>|null
     */
    #[HasMany(related: Category::class, foreignKey: 'parent_id')]
    public ?Relation $children = null;
}
