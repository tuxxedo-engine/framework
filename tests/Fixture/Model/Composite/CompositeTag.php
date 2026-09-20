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

namespace Fixture\Model\Composite;

use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'composite_tags')]
#[CompositeKey('realm', 'code')]
class CompositeTag
{
    #[Varchar(length: 64)]
    public string $realm = '';

    #[Varchar(length: 64)]
    public string $code = '';

    #[Varchar(length: 255)]
    public string $label = '';
}
