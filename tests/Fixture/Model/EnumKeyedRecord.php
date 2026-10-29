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

use Tuxxedo\Model\Attribute\Column\Enumeration;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'enum_keyed_records')]
class EnumKeyedRecord
{
    #[Enumeration(enum: PostStatus::class, primaryKey: true)]
    public PostStatus $status = PostStatus::DRAFT;

    #[Varchar(length: 255)]
    public string $label = '';
}
