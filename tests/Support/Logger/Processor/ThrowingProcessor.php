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

class ThrowingProcessor implements LogProcessorInterface
{
    public function __construct(
        public readonly string $message = 'processor failure',
    ) {
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        throw new \RuntimeException($this->message);
    }
}
