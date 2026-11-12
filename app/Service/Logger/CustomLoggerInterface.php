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

namespace App\Service\Logger;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\LoggerInterface;

#[DefaultImplementation(class: CustomLogger::class, lifecycle: Lifecycle::SINGLETON)]
interface CustomLoggerInterface extends LoggerInterface, \Countable
{
    /**
     * @var list<LogRecordInterface>
     */
    public array $records {
        get;
    }

    public function all(): string;
}
