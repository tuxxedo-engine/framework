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

use Tuxxedo\Container\DefaultLifecycle;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\Formatter\LineFormatter;
use Tuxxedo\Logger\MemoryLogger;

#[DefaultLifecycle(lifecycle: Lifecycle::SINGLETON)]
class CustomLogger extends MemoryLogger implements CustomLoggerInterface
{
    public function all(): string
    {
        $formatter = new LineFormatter();
        $list = '';

        foreach ($this->records as $record) {
            $list .= $formatter->format($record);
        }

        return $list;
    }
}
