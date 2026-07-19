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

use Tuxxedo\Model\Attribute\Column\BigInteger;
use Tuxxedo\Model\Attribute\Column\DateTime;
use Tuxxedo\Model\Attribute\Column\Decimal;
use Tuxxedo\Model\Attribute\Column\Enumeration;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'posts')]
class Post
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'user_id')]
    public int $userId = 0;

    #[Varchar(length: 255)]
    public string $title = '';

    #[Text]
    public string $body = '';

    #[Enumeration(enum: PostStatus::class)]
    public PostStatus $status = PostStatus::DRAFT;

    #[DateTime]
    public ?\DateTimeImmutable $publishedAt = null;

    #[BigInteger]
    public int $viewCount = 0;

    #[Decimal(precision: 10, scale: 2)]
    public string $rating = '0.00';

    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    public ?User $author = null;

    /**
     * @var Relation<Comment>|null
     */
    #[HasMany(related: Comment::class, foreignKey: 'post_id')]
    public ?Relation $comments = null;

    /**
     * @var Relation<Tag>|null
     */
    #[BelongsToMany(
        related: Tag::class,
        table: 'post_tag',
        localKey: 'post_id',
        foreignKey: 'tag_id',
    )]
    public ?Relation $tags = null;
}
