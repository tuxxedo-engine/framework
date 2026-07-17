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

namespace Fixture\Model\Broken;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'target_without_primary_key')]
class TargetWithoutPrimaryKey
{
    #[Integer]
    public int $someColumn = 0;

    #[Integer(name: 'owner_id')]
    public int $ownerId = 0;
}
