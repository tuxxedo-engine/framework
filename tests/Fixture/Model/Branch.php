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
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'branches')]
class Branch
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'region_id')]
    public int $regionId = 0;

    #[Integer(name: 'warehouse_id')]
    public int $warehouseId = 0;
}
