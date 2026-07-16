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

use Tuxxedo\Model\Attribute\Column\Boolean;
use Tuxxedo\Model\Attribute\Column\CreatedAt;
use Tuxxedo\Model\Attribute\Column\Double;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Timestamp;
use Tuxxedo\Model\Attribute\Column\UpdatedAt;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'users')]
class User
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    #[Varchar(length: 255)]
    public string $email = '';

    #[Boolean]
    public bool $isActive = true;

    #[Integer]
    public int $postCount = 0;

    #[Double]
    public float $score = 0.0;

    #[Timestamp]
    public ?\DateTimeImmutable $lastLoginAt = null;

    #[CreatedAt]
    public ?\DateTimeImmutable $createdAt = null;

    #[UpdatedAt]
    public ?\DateTimeImmutable $updatedAt = null;

    #[HasOne(related: Profile::class, foreignKey: 'user_id')]
    public ?Profile $profile = null;

    /**
     * @var Relation<Post>|null
     */
    #[HasMany(related: Post::class, foreignKey: 'user_id')]
    public ?Relation $posts = null;

    /**
     * @var Relation<Role>|null
     */
    #[BelongsToMany(
        related: Role::class,
        table: 'user_role',
        localKey: 'user_id',
        foreignKey: 'role_id',
    )]
    public ?Relation $roles = null;
}
