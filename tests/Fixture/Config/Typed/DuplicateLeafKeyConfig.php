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

namespace Fixture\Config\Typed;

use Tuxxedo\Config\Attribute\ConfigKey;
use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('duplicate')]
readonly class DuplicateLeafKeyConfig implements DuplicateLeafKeyConfigInterface
{
    public function __construct(
        public string $shared = '',
        #[ConfigKey('shared')]
        public string $other = '',
    ) {
    }
}
