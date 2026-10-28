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

namespace Tuxxedo\Router;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Reflection\EnumHydrator;

readonly class RouteArgument implements RouteArgumentInterface
{
    /**
     * @param class-string<\UnitEnum>|null $enumClass
     */
    public function __construct(
        public ArgumentNode $node,
        public ?string $mappedName,
        public string $nativeType,
        public bool $allowsNull,
        public mixed $defaultValue,
        public bool $resolverConsumed = false,
        public ?string $enumClass = null,
    ) {
    }

    public function getValue(
        array $matches,
    ): mixed {
        $value = $matches[$this->node->name] ?? $matches[$this->mappedName ?? ''] ?? null;

        if ($this->node->optional && ($value === null || $value === '')) {
            $value = $this->defaultValue;
        }

        if ($value === null && $this->allowsNull) {
            return null;
        }

        if ($this->enumClass !== null) {
            if ($value instanceof \UnitEnum) {
                return $value;
            }

            if (!\is_string($value)) {
                throw HttpException::fromNotFound();
            }

            return EnumHydrator::hydrate(
                enumClass: $this->enumClass,
                value: $value,
            ) ?? throw HttpException::fromNotFound();
        }

        \settype($value, $this->nativeType);

        return $value;
    }
}
