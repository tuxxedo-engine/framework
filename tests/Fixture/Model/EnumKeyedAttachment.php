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
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'enum_keyed_attachments')]
class EnumKeyedAttachment
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'host_type', length: 128)]
    public string $hostType = '';

    #[Varchar(name: 'host_id', length: 16)]
    public string $hostId = '';

    #[Varchar(length: 255)]
    public string $name = '';

    #[MorphTo(
        typeColumn: 'host_type',
        idColumn: 'host_id',
    )]
    public ?object $host = null;
}
