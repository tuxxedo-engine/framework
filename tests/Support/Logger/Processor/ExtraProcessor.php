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

namespace Support\Logger\Processor;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;

class ExtraProcessor implements LogProcessorInterface
{
    public int $callCount = 0;

    public function __construct(
        public readonly string $key = 'support.key',
        public readonly mixed $value = 'value',
    ) {
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        $this->callCount++;

        return $record->withExtra($this->key, $this->value);
    }
}
