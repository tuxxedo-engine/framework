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
use Tuxxedo\Model\Attribute\Column\SearchText;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'articles')]
class Article
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $title = '';

    #[Text(nullable: true)]
    public ?string $summary = null;

    #[SearchText(
        sources: [
            'title',
            'summary',
        ],
        name: 'search_text',
    )]
    public string $searchText = '';
}
