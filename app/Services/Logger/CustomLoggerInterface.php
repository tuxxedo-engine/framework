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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\LoggerInterface;

#[DefaultImplementation(class: CustomLogger::class, lifecycle: Lifecycle::PERSISTENT)]
interface CustomLoggerInterface extends LoggerInterface, \Countable
{
    /**
     * @var LogEntry[]
     */
    public array $entries {
        get;
    }

    public function all(): string;
}
