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

namespace Tuxxedo\Session;

use Tuxxedo\Container\AlwaysPersistentInterface;

class Session implements SessionInterface, AlwaysPersistentInterface
{
    public function __construct(
        public readonly SessionAdapterInterface $adapter,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->adapter->getIdentifier();
    }

    public function has(
        string $name,
    ): bool {
        return $this->adapter->has($name);
    }

    public function set(
        string $name,
        mixed $value,
    ): static {
        $this->adapter->set($name, $value);

        return $this;
    }

    public function all(): array
    {
        return $this->adapter->all();
    }

    public function getRaw(
        string $name,
        mixed $default = null,
    ): mixed {
        return $this->adapter->getRaw($name, $default);
    }

    public function getInt(
        string $name,
        int $default = 0,
    ): int {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_int($value)) {
            return $default;
        }

        return $value;
    }

    public function getBool(
        string $name,
        bool $default = false,
    ): bool {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_bool($value)) {
            return $default;
        }

        return $value;
    }

    public function getFloat(
        string $name,
        float $default = 0.0,
    ): float {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_float($value)) {
            return $default;
        }

        return $value;
    }

    public function getString(
        string $name,
        string $default = '',
    ): string {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_string($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum&\UnitEnum
     *
     * @throws SessionException
     */
    public function getEnum(
        string $name,
        string $enum,
    ): object {
        if (!\enum_exists($enum)) {
            throw SessionException::fromInvalidEnum(
                name: $name,
                enum: $enum,
            );
        }

        if (!\array_key_exists($name, $_SESSION)) {
            throw SessionException::fromInvalidEnum(
                name: $name,
                enum: $enum,
            );
        }

        $value = $this->getString($name);

        foreach ($enum::cases() as $case) {
            if (\strcasecmp($case->name, $value) === 0) {
                return $case;
            }
        }

        throw SessionException::fromInvalidEnum(
            name: $name,
            enum: $enum,
        );
    }
}
