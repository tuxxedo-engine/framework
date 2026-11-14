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
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'enum_keyed_record_tag')]
#[CompositeKey('recordStatus', 'tagId')]
class EnumKeyedRecordTag
{
    #[Enumeration(enum: PostStatus::class, name: 'record_status')]
    public PostStatus $recordStatus = PostStatus::DRAFT;

    #[Integer(name: 'tag_id')]
    public int $tagId = 0;
}
