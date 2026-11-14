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

namespace Fixture\Model\Pivot;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'pivot_labelables')]
class PivotLabelable
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'labelable_type', length: 255)]
    public string $labelableType = '';

    #[Integer(name: 'labelable_id')]
    public int $labelableId = 0;

    #[Integer(name: 'category_id')]
    public int $categoryId = 0;

    #[Integer]
    public int $weight = 0;
}
