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

namespace Fixture\Model\Uuid;

use Tuxxedo\Model\Attribute\Column\Ulid;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'ulid_records')]
class UlidRecord
{
    #[Ulid(primaryKey: true)]
    public ?string $id = null;

    #[Varchar(length: 255)]
    public string $label = '';
}
