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

namespace Support\Console\Attribute;

use Tuxxedo\Console\Attribute\RepeatableOptionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
readonly class ThirdPartyRepeatableOption implements RepeatableOptionInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $short = null,
        public ?string $description = null,
    ) {
    }
}
