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
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'mapped_comments')]
class MappedComment
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'commentable_type', length: 64)]
    public string $commentableType = '';

    #[Integer(name: 'commentable_id')]
    public int $commentableId = 0;

    #[Text]
    public string $body = '';

    #[MorphTo(
        typeColumn: 'commentable_type',
        idColumn: 'commentable_id',
        typeMap: [
            'article' => MappedArticle::class,
        ],
    )]
    public ?object $commentable = null;
}
