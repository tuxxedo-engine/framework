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

namespace Tuxxedo\Console\Output;

use Tuxxedo\Console\ConsoleException;

interface ProgressBarInterface
{
    /**
     * @throws ConsoleException
     */
    public function advance(
        int $steps = 1,
    ): void;

    public function setMessage(
        string $message,
    ): void;

    /**
     * @throws ConsoleException
     */
    public function finish(): void;

    /**
     * @template TKey
     * @template TValue
     *
     * @param array<TKey, TValue> $items
     * @return \Generator<TKey, TValue>
     *
     * @throws ConsoleException
     */
    public function iterate(
        array $items,
    ): \Generator;
}
