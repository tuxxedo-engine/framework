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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'composite_key_with_relation')]
#[CompositeKey('scope', 'name')]
class CompositeKeyWithRelation
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    #[Integer]
    public int $value = 0;

    /**
     * @var Relation<Post>|null
     */
    #[HasMany(
        related: Post::class,
        foreignKey: 'user_id',
    )]
    public ?Relation $posts = null;
}
