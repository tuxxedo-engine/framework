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

namespace Tuxxedo\Console\Descriptor;

class OptionDescriptor implements OptionDescriptorInterface
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $short,
        public readonly ?string $description,
        public readonly string $typeName,
        public readonly bool $isBuiltin,
        public readonly bool $isNullable,
        public readonly bool $hasDefault,
        public readonly mixed $default,
        public readonly bool $repeatable,
    ) {
    }
}
