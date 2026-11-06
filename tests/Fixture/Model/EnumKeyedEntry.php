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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'enum_keyed_entries')]
class EnumKeyedEntry
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Enumeration(enum: PostStatus::class)]
    public PostStatus $status = PostStatus::DRAFT;

    #[Varchar(length: 255)]
    public string $title = '';

    #[BelongsTo(related: EnumKeyedRecord::class, foreignKey: 'status')]
    public ?EnumKeyedRecord $record = null;

    #[BelongsTo(
        related: EnumKeyedNote::class,
        foreignKey: 'status',
        ownerKey: 'status',
    )]
    public ?EnumKeyedNote $note = null;
}
