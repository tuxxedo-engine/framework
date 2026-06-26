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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: Session::class, lifecycle: Lifecycle::SINGLETON)]
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
    public function remove(
        string $name,
    ): void;

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
    public function raw(
        string $name,
        mixed $default = null,
    ): mixed;

    /**
     * @throws SessionException
     */
    public function int(
        string $name,
        int $default = 0,
    ): int;

    /**
     * @throws SessionException
     */
    public function bool(
        string $name,
        bool $default = false,
    ): bool;

    /**
     * @throws SessionException
     */
    public function float(
        string $name,
        float $default = 0.0,
    ): float;

    /**
     * @throws SessionException
     */
    public function string(
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
    public function enum(
        string $name,
        string $enum,
    ): object;
}
