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
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;
use Tuxxedo\Logger\SyslogLogger;
use Tuxxedo\Temporal\ClockInterface;

class SyslogLoggerConfig extends AbstractLoggerConfig implements SyslogLoggerConfigInterface
{
    /**
     * @param list<class-string<LogProcessorInterface>|LogProcessorInterface> $processors
     */
    public function __construct(
        public readonly string $ident = 'tuxxedo',
        public readonly bool $persistent = false,
        public readonly int $facility = \LOG_USER,
        public readonly int $options = \LOG_PID | \LOG_ODELAY,
        public readonly ?LogFormat $format = null,
        public readonly LogFailureMode $unencodable = LogFailureMode::MARKER,
        array $processors = [],
        LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ) {
        parent::__construct(
            processors: $processors,
            processorFailure: $processorFailure,
        );
    }

    public function createLogger(
        ContainerInterface $container,
    ): LoggerInterface {
        return new SyslogLogger(
            container: $container,
            ident: $this->ident,
            persistent: $this->persistent,
            facility: $this->facility,
            options: $this->options,
            formatter: $this->format?->createFormatter(
                unencodable: $this->unencodable,
            ),
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
