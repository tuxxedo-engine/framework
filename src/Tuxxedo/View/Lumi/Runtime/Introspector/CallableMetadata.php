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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

readonly class CallableMetadata implements CallableMetadataInterface
{
    /**
     * @param class-string $className
     * @param list<string> $aliases
     */
    public function __construct(
        public string $name,
        public CallableKind $kind,
        public string $className,
        public string $methodName,
        public bool $wantsContext,
        public ?int $contextParameterIndex = null,
        public array $aliases = [],
    ) {
    }
}
