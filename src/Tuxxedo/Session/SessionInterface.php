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

use Tuxxedo\Container\DefaultImplementation;

#[DefaultImplementation(class: Session::class)]
interface SessionInterface
{
    public SessionAdapterInterface $adapter {
        get;
    }

    /**
     * @throws SessionException
     */
    public function getIdentifier(): string;

    /**
     * @throws SessionException
     */
    public function has(
        string $name,
    ): bool;

    /**
     * @throws SessionException
     */
    public function set(
        string $name,
        mixed $value,
    ): static;

    /**
     * @return mixed[]
     *
     * @throws SessionException
     */
    public function all(): array;

    /**
     * @throws SessionException
     */
    public function getRaw(
        string $name,
        mixed $default = null,
    ): mixed;

    /**
     * @throws SessionException
     */
    public function getInt(
        string $name,
        int $default = 0,
    ): int;

    /**
     * @throws SessionException
     */
    public function getBool(
        string $name,
        bool $default = false,
    ): bool;

    /**
     * @throws SessionException
     */
    public function getFloat(
        string $name,
        float $default = 0.0,
    ): float;

    /**
     * @throws SessionException
     */
    public function getString(
        string $name,
        string $default = '',
    ): string;

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
    ): object;
}
