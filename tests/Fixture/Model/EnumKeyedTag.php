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
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'enum_keyed_tags')]
class EnumKeyedTag
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 64)]
    public string $name = '';

    /**
     * @var Relation<EnumKeyedRecord>|null
     */
    #[BelongsToMany(
        related: EnumKeyedRecord::class,
        table: 'enum_keyed_record_tag',
        localKey: 'tag_id',
        foreignKey: 'record_status',
        pivot: EnumKeyedRecordTag::class,
    )]
    public ?Relation $records = null;
}
