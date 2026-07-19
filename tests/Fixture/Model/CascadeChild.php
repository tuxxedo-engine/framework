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
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'cascade_children')]
class CascadeChild
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'auto_group_id')]
    public ?int $autoGroupId = null;

    #[Integer(name: 'restrict_group_id')]
    public ?int $restrictGroupId = null;

    #[Integer(name: 'nullable_group_id')]
    public ?int $nullableGroupId = null;

    #[Integer(name: 'noaction_group_id')]
    public ?int $noActionGroupId = null;

    #[Varchar(length: 255)]
    public string $label = '';
}
