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
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;

#[Table(name: 'cascade_bt_children')]
class CascadeBelongsToChild
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'parent_id')]
    public ?int $parentId = null;

    #[Varchar(length: 255)]
    public string $label = '';

    #[BelongsTo(
        related: CascadeBelongsToParent::class,
        foreignKey: 'parent_id',
        onDelete: CascadeAction::CASCADE,
    )]
    public ?CascadeBelongsToParent $parent = null;
}
