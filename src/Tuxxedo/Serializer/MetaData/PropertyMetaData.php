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

namespace Tuxxedo\Serializer\MetaData;

use Tuxxedo\Serializer\ValueConverterInterface;

class PropertyMetaData
{
    public function __construct(
        public readonly string $name,
        public readonly string $key,
        public readonly ?string $typeName,
        public readonly bool $builtin,
        public readonly bool $nullable,
        public readonly bool $hasDefault,
        public readonly bool $constructorParameter,
        public readonly bool $readable,
        public readonly bool $ignore = false,
        public readonly ?string $listOf = null,
        public readonly ?string $mapOf = null,
        public readonly ?ValueConverterInterface $converter = null,
    ) {
    }
}
