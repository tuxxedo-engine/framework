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
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\HasOneThrough;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'enum_keyed_records')]
class EnumKeyedRecord
{
    #[Enumeration(enum: PostStatus::class, primaryKey: true)]
    public PostStatus $status = PostStatus::DRAFT;

    #[Varchar(length: 255)]
    public string $label = '';

    /**
     * @var Relation<EnumKeyedEntry>|null
     */
    #[HasMany(related: EnumKeyedEntry::class, foreignKey: 'status')]
    public ?Relation $entries = null;

    #[HasOne(related: EnumKeyedNote::class, foreignKey: 'status')]
    public ?EnumKeyedNote $note = null;

    /**
     * @var Relation<EnumKeyedComment>|null
     */
    #[HasManyThrough(
        related: EnumKeyedComment::class,
        through: EnumKeyedEntry::class,
        firstKey: 'status',
        secondKey: 'entry_id',
    )]
    public ?Relation $comments = null;

    #[HasOneThrough(
        related: EnumKeyedComment::class,
        through: EnumKeyedEntry::class,
        firstKey: 'status',
        secondKey: 'entry_id',
    )]
    public ?EnumKeyedComment $firstComment = null;

    /**
     * @var Relation<EnumKeyedTag>|null
     */
    #[BelongsToMany(
        related: EnumKeyedTag::class,
        table: 'enum_keyed_record_tag',
        localKey: 'record_status',
        foreignKey: 'tag_id',
    )]
    public ?Relation $tags = null;

    /**
     * @var Relation<EnumKeyedAttachment>|null
     */
    #[MorphMany(
        related: EnumKeyedAttachment::class,
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?Relation $attachments = null;

    #[MorphOne(
        related: EnumKeyedAttachment::class,
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?EnumKeyedAttachment $attachment = null;
}
