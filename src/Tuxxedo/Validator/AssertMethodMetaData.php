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

class AssertMethodMetaData
{
    /**
     * @param list<string> $contextParameterNames
     */
    public function __construct(
        public readonly string $methodName,
        public readonly array $contextParameterNames,
    ) {
    }
}
