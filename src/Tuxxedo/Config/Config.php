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

namespace Tuxxedo\Config;

use Tuxxedo\Collections\FileCollection;

// @todo Consider a $default parameter?
class Config implements ConfigInterface
{
    /**
     * @param array<mixed> $directives
     */
    final public function __construct(
        protected array $directives = [],
    ) {
    }

    /**
     * @return array<mixed>
     */
    protected static function isolatedInclude(string $configFileName): array
    {
        return (static fn(): array => (array) require $configFileName)();
    }

    public static function createFromDirectory(string $directory): static
    {
        $directives = [];

        foreach (FileCollection::fromFileType($directory, 'php') as $configFile) {
            $directives[\basename($configFile, '.php')] = static::isolatedInclude($configFile);
        }

        return new static(
            directives: $directives,
        );
    }

    public static function createFromFile(string $file): static
    {
        return new static(
            directives: [
                ...static::isolatedInclude($file),
            ],
        );
    }

    public function has(string $path): bool
    {
        try {
            $this->path($path);
        } catch (ConfigException) {
            return false;
        }

        return true;
    }

    public function path(string $path): mixed
    {
        $index = $this->directives;

        foreach (\explode('.', $path) as $part) {
            if (!\array_key_exists($part, $index)) {
                throw ConfigException::fromInvalidDirective(
                    directive: $path,
                );
            }

            if (!\is_array($index[$part])) {
                return $index[$part];
            }

            $index = $index[$part];
        }

        if ($index === $this->directives) {
            throw ConfigException::fromInvalidDirective(
                directive: $path,
            );
        }

        return $index;
    }

    public function getInt(string $path): int
    {
        $value = $this->path($path);

        if (!\is_int($value)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: 'int',
            );
        }

        return $value;
    }

    public function getBool(string $path): bool
    {
        $value = $this->path($path);

        if (!\is_bool($value)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: 'bool',
            );
        }

        return $value;
    }

    public function getFloat(string $path): float
    {
        $value = $this->path($path);

        if (!\is_float($value)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: 'float',
            );
        }

        return $value;
    }

    public function getString(string $path): string
    {
        $value = $this->path($path);

        if (!\is_string($value)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: 'string',
            );
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $enum
     * @return T&\UnitEnum
     */
    public function getEnum(string $path, string $enum): object
    {
        $value = $this->path($path);

        if (!\is_object($value) || !\enum_exists($enum) || !\is_a($value, $enum)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: $enum,
            );
        }

        /** @var T&\UnitEnum */
        return $value;
    }
}
