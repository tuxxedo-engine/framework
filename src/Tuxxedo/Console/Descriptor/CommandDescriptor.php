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

class CommandDescriptor implements CommandDescriptorInterface
{
    /**
     * @param list<string> $path
     * @param list<ArgumentDescriptorInterface> $arguments
     * @param list<OptionDescriptorInterface> $options
     * @param list<FlagDescriptorInterface> $flags
     * @param class-string $className
     */
    public function __construct(
        public readonly array $path,
        public readonly ?string $description,
        public readonly bool $hasReturnValue,
        public readonly array $arguments,
        public readonly array $options,
        public readonly array $flags,
        public readonly string $className,
        public readonly string $methodName,
    ) {
    }
}
