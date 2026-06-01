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

namespace Tuxxedo\Database\Driver;

use Tuxxedo\Database\Hydrator\HydratableInterface;

class ResultRow implements ResultRowInterface, HydratableInterface
{
    /**
     * @param array<string, mixed> $properties
     */
    final public function __construct(
        public readonly array $properties,
    ) {
    }

    public static function create(
        array $properties,
    ): static {
        return new static($properties);
    }

    public function __get(string $property): mixed
    {
        return $this->properties[$property] ?? null;
    }
}
