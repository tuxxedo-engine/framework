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

#[ConfigNamespace('override')]
readonly class KeyOverrideConfig implements KeyOverrideConfigInterface
{
    public function __construct(
        #[ConfigKey('renamed')]
        public string $sourceProperty = '',
    ) {
    }
}
