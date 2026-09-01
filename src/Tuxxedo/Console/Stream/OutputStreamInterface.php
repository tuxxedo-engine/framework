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

interface OutputStreamInterface
{
    public bool $isTerminal {
        get;
    }

    /**
     * @throws ConsoleException
     */
    public function write(
        string $bytes,
    ): void;

    public function close(): void;
}
