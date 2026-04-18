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

namespace Tuxxedo\View\Lumi\Library\Attribute;

#[\Attribute(flags: \Attribute::TARGET_METHOD)]
readonly class LumiFilter
{
    /**
     * @param string[] $aliases
     */
    public function __construct(
        public string $name,
        public array $aliases = [],
    ) {
    }
}
