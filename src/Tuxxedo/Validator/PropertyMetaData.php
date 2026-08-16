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

namespace Tuxxedo\Validator;

class PropertyMetaData
{
    /**
     * @param list<RuleInterface> $rules
     */
    public function __construct(
        public readonly string $name,
        public readonly array $rules,
    ) {
    }
}
