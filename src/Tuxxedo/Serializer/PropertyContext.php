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

namespace Tuxxedo\Serializer;

class PropertyContext implements PropertyContextInterface
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public readonly string $className,
        public readonly string $propertyName,
        public readonly string $path,
        public readonly ?string $typeName,
        public readonly bool $nullable,
        public readonly Casting $casting,
    ) {
    }
}
