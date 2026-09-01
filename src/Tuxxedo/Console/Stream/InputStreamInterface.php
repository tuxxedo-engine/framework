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

namespace Tuxxedo\Console\Stream;

use Tuxxedo\Console\ConsoleException;

interface InputStreamInterface
{
    public bool $isTerminal {
        get;
    }

    /**
     * @param positive-int $length
     *
     * @throws ConsoleException
     */
    public function read(
        int $length,
    ): string;

    /**
     * @throws ConsoleException
     */
    public function readLine(): ?string;

    /**
     * @throws ConsoleException
     */
    public function readAll(): string;

    public function close(): void;
}
