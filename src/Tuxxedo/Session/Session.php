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

namespace Tuxxedo\Session;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Session\Config\SessionConfigInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): SessionInterface {
        return new Session(
            adapter: Adapter\PhpSessionAdapter::createFromConfig(
                startMode: SessionStartMode::LAZY,
                config: $container->resolve(SessionConfigInterface::class),
            ),
        );
    },
)]
class Session implements SessionInterface
{
    public function __construct(
        public readonly SessionAdapterInterface $adapter,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->adapter->getIdentifier();
    }

    public function remove(
        string $name,
    ): void {
        $this->adapter->remove($name);
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

    public function raw(
        string $name,
        mixed $default = null,
    ): mixed {
        return $this->adapter->getRaw($name, $default);
    }

    public function int(
        string $name,
        int $default = 0,
    ): int {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_int($value)) {
            return $default;
        }

        return $value;
    }

    public function bool(
        string $name,
        bool $default = false,
    ): bool {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_bool($value)) {
            return $default;
        }

        return $value;
    }

    public function float(
        string $name,
        float $default = 0.0,
    ): float {
        $value = $this->adapter->getRaw($name, $default);

        if (!\is_float($value)) {
            return $default;
        }

        return $value;
    }

    public function string(
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
    public function enum(
        string $name,
        string $enum,
    ): object {
        if (!\enum_exists($enum)) {
            // @codeCoverageIgnoreStart
            throw SessionException::fromInvalidEnum(
                name: $name,
                enum: $enum,
            );
            // @codeCoverageIgnoreEnd
        }

        if (!\array_key_exists($name, $this->adapter->all())) {
            throw SessionException::fromInvalidEnum(
                name: $name,
                enum: $enum,
            );
        }

        $value = $this->string($name);

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
