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
use Tuxxedo\Model\CascadeAction;

#[Table(name: 'hosted_comments')]
class HostedComment
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'host_type', length: 64)]
    public string $hostType = '';

    #[Integer(name: 'host_id')]
    public int $hostId = 0;

    #[Text]
    public string $body = '';

    #[MorphTo(
        typeColumn: 'host_type',
        idColumn: 'host_id',
        onSave: CascadeAction::CASCADE,
    )]
    public ?object $host = null;
}
