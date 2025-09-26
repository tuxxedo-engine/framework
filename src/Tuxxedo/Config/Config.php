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

use Tuxxedo\Collection\FileCollection;

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
    protected static function isolatedInclude(
        string $configFileName,
    ): array {
        return (static fn (): array => (array) require $configFileName)();
    }

    public static function createFromDirectory(
        string $directory,
    ): static {
        $directives = [];

        foreach (FileCollection::fromRecursiveFileType($directory, '.php') as $configFile) {
            $index = \str_replace($directory . '/', '', \substr($configFile, 0, -4));

            if (\str_contains($index, '/')) {
                $parts = \explode('/', $index);
                $leaf = \array_pop($parts);
                $ref = &$directives;

                foreach ($parts as $part) {
                    /** @var array<mixed> $ref */
                    $ref[$part] ??= [];
                    $ref = &$ref[$part];
                }

                /** @var array<mixed> $ref */
                $ref[$leaf] = static::isolatedInclude($configFile);

                unset($ref);
            } else {
                $directives[$index] = static::isolatedInclude($configFile);
            }
        }

        return new static(
            directives: $directives,
        );
    }

    public static function createFromFile(
        string $file,
    ): static {
        return new static(
            directives: [
                ...static::isolatedInclude($file),
            ],
        );
    }

    public function has(
        string $path,
    ): bool {
        try {
            $this->path($path);
        } catch (ConfigException) {
            return false;
        }

        return true;
    }

    public function path(
        string $path,
    ): mixed {
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

    public function section(
        string $path,
    ): self {
        $section = $this->path($path);

        if (!\is_array($section)) {
            throw ConfigException::fromInvalidSection(
                directive: $path,
            );
        }

        return new static(
            directives: $section,
        );
    }

    public function getInt(
        string $path,
    ): int {
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

    public function getBool(
        string $path,
    ): bool {
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

    public function getFloat(
        string $path,
    ): float {
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

    public function getString(
        string $path,
    ): string {
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
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum
     */
    public function getEnum(
        string $path,
        string $enum,
    ): object {
        $value = $this->path($path);

        if (!\is_object($value) || !\enum_exists($enum) || !\is_a($value, $enum)) {
            throw ConfigException::fromUnexpectedDirectiveType(
                directive: $path,
                actualType: \get_debug_type($value),
                expectedType: $enum,
            );
        }

        return $value;
    }
}
