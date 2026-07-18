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
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'bulk_parents')]
class BulkParent
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    /**
     * @var Relation<BulkChild>|null
     */
    #[HasMany(
        related: BulkChild::class,
        foreignKey: 'parent_id',
        localKey: 'id',
        onDelete: CascadeAction::CASCADE,
        bulkDelete: true,
    )]
    public ?Relation $children = null;
}
