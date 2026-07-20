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
use Tuxxedo\Model\Attribute\Identifier;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'identifier_already_unique')]
class IdentifierAlreadyUnique
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Identifier]
    #[Varchar(length: 64, unique: true)]
    public string $slug = '';

    #[Varchar(length: 128)]
    public string $label = '';
}
