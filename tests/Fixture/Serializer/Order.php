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

namespace Fixture\Serializer;

use Tuxxedo\Serializer\Attribute\Convert;

class Order
{
    public function __construct(
        #[Convert(MoneyConverter::class)]
        public readonly Money $total,
        #[YesNo]
        public readonly bool $paid,
    ) {
    }
}
