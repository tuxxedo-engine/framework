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

namespace Fixture\Model\Aggregate;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'aggregate_poly_tags')]
#[CompositeKey('realm', 'code')]
class AggregatePolyTag
{
    #[Varchar(length: 32)]
    public string $realm = '';

    #[Varchar(length: 32)]
    public string $code = '';

    #[Varchar(length: 64)]
    public string $label = '';

    #[Integer]
    public int $weight = 0;
}
