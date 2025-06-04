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

namespace App\Services\Logger;

class Logger implements LoggerInterface
{
    /**
     * @var LogEntry[]
     */
    private array $entries = [];

    public function log(
        LogEntry|string $entry,
    ): static {
        if (!$entry instanceof LogEntry) {
            $entry = new LogEntry(
                date: \date('H:i:s j/n - Y'),
                message: $entry,
            );
        }

        $this->entries[] = $entry;

        return $this;
    }

    public function formatEntries(): string
    {
        $list = '';

        foreach ($this->entries as $entry) {
            $list .= \sprintf(
                "[%s] %s\n",
                $entry->date,
                $entry->message,
            );
        }

        return $list;
    }
}
