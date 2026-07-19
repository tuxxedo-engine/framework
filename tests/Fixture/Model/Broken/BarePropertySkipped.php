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

#[Table(name: 'bare_property_skipped')]
class BarePropertySkipped
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    public int $unattributed = 0;
}
