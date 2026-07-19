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

#[Table(name: 'bulk_children')]
class BulkChild
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'parent_id')]
    public int $parentId = 0;

    #[Varchar(length: 255)]
    public string $label = '';
}
