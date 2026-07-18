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
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'readonly_records')]
class ReadonlyRecord
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    public function __construct(
        #[Varchar(length: 255)]
        public readonly string $name = '',
    ) {
    }
}
