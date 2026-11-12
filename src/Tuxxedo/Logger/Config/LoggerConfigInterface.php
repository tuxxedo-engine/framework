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

namespace Tuxxedo\Logger\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerException;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;

#[DefaultImplementation(class: NullLoggerConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface LoggerConfigInterface
{
    /**
     * @var list<class-string<LogProcessorInterface>|LogProcessorInterface>
     */
    public array $processors {
        get;
    }

    public LogFailureMode $processorFailure {
        get;
    }

    /**
     * @throws LoggerException
     */
    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface;
}
