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

namespace Tuxxedo\Logger;

interface LogMessageFormatterInterface
{
    /**
     * @param array<string, scalar> $placeholders
     */
    public function format(
        string $message,
        array $placeholders = [],
        ?LogLevel $level = null,
        ?\DateTimeImmutable $timestamp = null,
    ): string;
}
