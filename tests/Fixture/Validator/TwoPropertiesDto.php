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

namespace Fixture\Validator;

class TwoPropertiesDto
{
    public function __construct(
        #[AlwaysFailRule]
        public readonly string $first,
        #[AlwaysPassRule]
        public readonly string $second,
    ) {
    }
}
