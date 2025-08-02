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

namespace Tuxxedo\Router;

readonly class RouteArgument implements RouteArgumentInterface
{
    public function __construct(
        public ArgumentNode $node,
        public ?string $mappedName,
        public string $nativeType,
        public mixed $defaultValue,
    ) {
    }

    public function getValue(
        array $matches,
    ): mixed {
        $value = $matches[$this->node->name] ?? $matches[$this->mappedName ?? ''] ?? null;

        if ($this->node->optional) {
            $value ??= $this->defaultValue;
        }

        \settype($value, $this->nativeType);

        return $value;
    }
}
