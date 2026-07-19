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
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'countries')]
class Country
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    #[Char(length: 2)]
    public string $code = '';

    /**
     * @var Relation<User>|null
     */
    #[HasMany(
        related: User::class,
        foreignKey: 'country_id',
    )]
    public ?Relation $users = null;

    /**
     * @var Relation<Post>|null
     */
    #[HasManyThrough(
        related: Post::class,
        through: User::class,
        firstKey: 'country_id',
        secondKey: 'user_id',
    )]
    public ?Relation $posts = null;

    #[HasOneThrough(
        related: Post::class,
        through: User::class,
        firstKey: 'country_id',
        secondKey: 'user_id',
    )]
    public ?Post $firstPost = null;
}
