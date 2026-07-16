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

use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'settings')]
#[CompositeKey('scope', 'name')]
class Setting
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    #[Text]
    public string $value = '';
}
