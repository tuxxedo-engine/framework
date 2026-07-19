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
use Tuxxedo\Model\Attribute\Index;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'class_level_index')]
#[Index('status')]
#[Index('status', 'created_at')]
class ClassLevelIndex
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 32)]
    public string $status = '';

    #[Varchar(name: 'created_at', length: 32)]
    public string $createdAt = '';
}
