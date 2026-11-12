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
use Tuxxedo\Logger\StreamLogger;
use Tuxxedo\Temporal\ClockInterface;

class StreamLoggerConfig extends AbstractLoggerConfig implements StreamLoggerConfigInterface
{
    /**
     * @param list<class-string<LogProcessorInterface>|LogProcessorInterface> $processors
     */
    public function __construct(
        public readonly string $file,
        public readonly bool $append = true,
        public readonly bool $autoFlush = true,
        public readonly LogFormat $format = LogFormat::LINE,
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
        return StreamLogger::createFromFile(
            container: $container,
            file: $this->file,
            autoFlush: $this->autoFlush,
            append: $this->append,
            formatter: $this->format->createFormatter(
                unencodable: $this->unencodable,
            ),
            clock: $container->resolve(ClockInterface::class),
            processors: $this->createProcessors($container),
            processorFailure: $this->processorFailure,
        );
    }
}
