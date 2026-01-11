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

use Tuxxedo\Container\AlwaysPersistentInterface;
use Tuxxedo\Container\DefaultImplementation;

#[DefaultImplementation(class: Logger::class)]
interface LoggerInterface extends \Countable, AlwaysPersistentInterface
{
    /**
     * @var LogEntry[]
     */
    public array $entries {
        get;
    }

    public function log(
        LogEntry|string $entry,
    ): static;

    public function formatEntries(): string;
}
