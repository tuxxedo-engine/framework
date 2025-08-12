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

namespace Tuxxedo\Router\Patterns;

class TypePatternRegistry implements TypePatternRegistryInterface
{
    /**
     * @var array<string, TypePatternInterface>
     */
    public private(set) array $patterns;

    /**
     * @param TypePatternInterface[] $patterns
     */
    final private function __construct(
        array $patterns,
    ) {
        $this->patterns = [];

        foreach ($patterns as $pattern) {
            $this->patterns[$pattern->name] = $pattern;
        }
    }

    /**
     * @return TypePatternInterface[]
     */
    private static function getDefaults(): array
    {
        return [
            new Type\Alpha(),
            new Type\Boolean(),
            new Type\CountryCode(),
            new Type\CurrencyCode(),
            new Type\Date(),
            new Type\Hex(),
            new Type\LanguageCode(),
            new Type\NumericId(),
            new Type\Sha1(),
            new Type\Sha256(),
            new Type\Slug(),
            new Type\Timestamp(),
            new Type\Uuid(),
            new Type\UuidV4(),
        ];
    }

    public static function createDefault(): static
    {
        return new static(
            patterns: self::getDefaults(),
        );
    }

    /**
     * @param TypePatternInterface[] $patterns
     */
    public static function createWithDefaults(
        array $patterns,
    ): static {
        return new static(
            patterns: \array_merge(
                self::getDefaults(),
                $patterns,
            ),
        );
    }

    /**
     * @param TypePatternInterface[] $patterns
     */
    public static function createWithoutDefaults(
        array $patterns,
    ): static {
        return new static(
            patterns: $patterns,
        );
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->patterns);
    }

    public function get(string $name): ?TypePatternInterface
    {
        return $this->patterns[$name] ?? null;
    }
}
