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

namespace Fixture\Model\Composite;

use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'composite_projects')]
#[CompositeKey('space', 'slug')]
class CompositeProject
{
    #[Varchar(length: 64)]
    public string $space = '';

    #[Varchar(length: 64)]
    public string $slug = '';

    #[Varchar(name: 'owner_scope', length: 64)]
    public string $ownerScope = '';

    #[Varchar(name: 'owner_name', length: 64)]
    public string $ownerName = '';
}
