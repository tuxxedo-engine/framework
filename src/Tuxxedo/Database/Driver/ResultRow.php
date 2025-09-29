<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database\Driver;

class ResultRow implements ResultRowInterface
{
    /**
     * @param mixed[] $properties
     */
    public function __construct(
        public readonly array $properties,
    ) {
    }

    public function __get(string $property): mixed
    {
        return $this->properties[$property] ?? null;
    }
}
