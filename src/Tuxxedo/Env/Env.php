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

namespace Tuxxedo\Env;

use Tuxxedo\Env\Source\EnvSourceInterface;

class Env implements EnvInterface
{
    /**
     * @var list<EnvSourceInterface>
     */
    private readonly array $sources;

    public function __construct(
        EnvSourceInterface ...$sources,
    ) {
        $this->sources = \array_values($sources);
    }

    public function has(
        string $key,
    ): bool {
        foreach ($this->sources as $source) {
            if ($source->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function string(
        string $key,
        ?string $default = null,
    ): string {
        $value = $this->lookup(
            key: $key,
        );

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }

            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        return $this->stringify(
            value: $value,
        );
    }

    public function int(
        string $key,
        ?int $default = null,
    ): int {
        $value = $this->lookup(
            key: $key,
        );

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }

            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && \preg_match('/\A-?\d+\z/', $value) === 1) {
            return (int) $value;
        }

        throw EnvException::fromInvalidCoercion(
            key: $key,
            expectedType: 'int',
            value: $this->stringify(
                value: $value,
            ),
        );
    }

    public function bool(
        string $key,
        ?bool $default = null,
    ): bool {
        $value = $this->lookup(
            key: $key,
        );

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }

            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        if (\is_bool($value)) {
            return $value;
        }

        if ($value === 1) {
            return true;
        }

        if ($value === 0) {
            return false;
        }

        if (\is_string($value)) {
            $matched = match (\strtolower($value)) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off', '' => false,
                default => null,
            };

            if ($matched !== null) {
                return $matched;
            }
        }

        throw EnvException::fromInvalidCoercion(
            key: $key,
            expectedType: 'bool',
            value: $this->stringify(
                value: $value,
            ),
        );
    }

    public function float(
        string $key,
        ?float $default = null,
    ): float {
        $value = $this->lookup(
            key: $key,
        );

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }

            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        if (\is_float($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return (float) $value;
        }

        if (\is_string($value) && \preg_match('/\A-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\z/', $value) === 1) {
            return (float) $value;
        }

        throw EnvException::fromInvalidCoercion(
            key: $key,
            expectedType: 'float',
            value: $this->stringify(
                value: $value,
            ),
        );
    }

    /**
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     * @param T|null $default
     * @return T
     */
    public function enum(
        string $key,
        string $enum,
        ?\UnitEnum $default = null,
    ): \UnitEnum {
        $value = $this->lookup(
            key: $key,
        );

        if ($value === null) {
            if ($default !== null) {
                return $default;
            }

            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        $stringValue = $this->stringify(
            value: $value,
        );

        if (\is_subclass_of($enum, \BackedEnum::class)) {
            $backingType = (new \ReflectionEnum($enum))->getBackingType();

            if (
                $backingType instanceof \ReflectionNamedType &&
                $backingType->getName() === 'int' &&
                \preg_match('/\A-?\d+\z/', $stringValue) === 1
            ) {
                $case = $enum::tryFrom((int) $stringValue);
            } elseif (
                $backingType instanceof \ReflectionNamedType &&
                $backingType->getName() === 'string'
            ) {
                $case = $enum::tryFrom($stringValue);
            } else {
                $case = null;
            }

            if ($case === null) {
                throw EnvException::fromInvalidEnum(
                    key: $key,
                    enum: $enum,
                    value: $stringValue,
                );
            }

            return $case;
        }

        foreach ($enum::cases() as $case) {
            if ($case->name === $stringValue) {
                return $case;
            }
        }

        throw EnvException::fromInvalidEnum(
            key: $key,
            enum: $enum,
            value: $stringValue,
        );
    }

    private function lookup(
        string $key,
    ): string|int|float|bool|null {
        foreach ($this->sources as $source) {
            if ($source->has($key)) {
                return $source->get(
                    key: $key,
                );
            }
        }

        return null;
    }

    private function stringify(
        string|int|float|bool $value,
    ): string {
        if (\is_bool($value)) {
            return $value
                ? 'true'
                : 'false';
        }

        return (string) $value;
    }
}
