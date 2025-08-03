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
    public function __construct(
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
            new Types\Alpha(),
            new Types\Boolean(),
            new Types\CountryCode(),
            new Types\CurrencyCode(),
            new Types\Date(),
            new Types\Datetime(),
            new Types\Hex(),
            new Types\LanguageCode(),
            new Types\NumericId(),
            new Types\Sha1(),
            new Types\Sha256(),
            new Types\Slug(),
            new Types\Timestamp(),
            new Types\Uuid(),
            new Types\UuidV4(),
        ];
    }

    public static function createDefault(): TypePatternRegistryInterface
    {
        return new self(
            patterns: self::getDefaults(),
        );
    }

    /**
     * @param TypePatternInterface[] $patterns
     */
    public static function createWithDefaults(
        array $patterns,
    ): TypePatternRegistryInterface {
        return new self(
            patterns: \array_merge(
                self::getDefaults(),
                $patterns,
            ),
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
