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

interface SessionAdapterInterface
{
    public SessionStartMode $startMode {
        get;
    }

    public function isStarted(): bool;

    /**
     * @throws SessionException
     */
    public function start(): static;

    /**
     * @throws SessionException
     */
    public function stop(): static;

    /**
     * @throws SessionException
     */
    public function restart(): static;

    /**
     * @throws SessionException
     */
    public function unset(): static;

    /**
     * @throws SessionException
     */
    public function getIdentifier(): string;

    /**
     * @throws SessionException
     */
    public function regenerateIdentifier(): static;

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
     */
    public function all(): array;

    /**
     * @throws SessionException
     */
    public function getRaw(
        string $name,
        mixed $default = null,
    ): mixed;
}
