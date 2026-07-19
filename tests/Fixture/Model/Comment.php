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

use Tuxxedo\Model\Attribute\Column\CreatedAt;
use Tuxxedo\Model\Attribute\Column\DeletedAt;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'comments')]
class Comment
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'post_id')]
    public int $postId = 0;

    #[Integer(name: 'user_id')]
    public int $userId = 0;

    #[Text]
    public string $body = '';

    #[CreatedAt]
    public ?\DateTimeImmutable $createdAt = null;

    #[DeletedAt]
    public ?\DateTimeImmutable $deletedAt = null;

    #[BelongsTo(related: Post::class, foreignKey: 'post_id')]
    public ?Post $post = null;

    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    public ?User $author = null;
}
