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

namespace Tuxxedo\Logger\Processor;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\Info\MemoryInfo;

class MemoryProcessor implements LogProcessorInterface
{
    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        return $record->withExtra(
            'memory',
            new MemoryInfo(
                usage: \memory_get_usage(true),
                peak: \memory_get_peak_usage(true),
            ),
        );
    }
}
