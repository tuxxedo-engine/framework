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

use Tuxxedo\Model\Attribute\Column\Uuid;
use Tuxxedo\Model\Attribute\Column\UuidVersion;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'uuid_v4_explicits')]
class UuidV4Explicit
{
    #[Uuid(version: UuidVersion::ANY, primaryKey: true)]
    public ?string $id = null;

    #[Varchar(length: 255)]
    public string $label = '';
}
