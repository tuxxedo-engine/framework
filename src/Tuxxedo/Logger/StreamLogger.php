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

namespace Tuxxedo\Logger;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\Formatter\LineFormatter;
use Tuxxedo\Logger\Formatter\LogFormatterInterface;
use Tuxxedo\Logger\Processor\LogProcessorInterface;
use Tuxxedo\Temporal\ClockInterface;

class StreamLogger extends AbstractLogger
{
    private readonly LogFormatterInterface $formatter;

    /**
     * @param resource $stream
     * @param list<LogProcessorInterface> $processors
     */
    final public function __construct(
        ContainerInterface $container,
        private readonly mixed $stream,
        public bool $autoFlush = true,
        ?LogFormatterInterface $formatter = null,
        ?ClockInterface $clock = null,
        array $processors = [],
        LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ) {
        parent::__construct(
            container: $container,
            clock: $clock,
            processors: $processors,
            processorFailure: $processorFailure,
        );

        $this->formatter = $formatter ?? new LineFormatter();
    }

    /**
     * @param list<LogProcessorInterface> $processors
     *
     * @throws LoggerException
     */
    public static function createFromFile(
        ContainerInterface $container,
        string $file,
        bool $autoFlush = true,
        bool $append = true,
        ?LogFormatterInterface $formatter = null,
        ?ClockInterface $clock = null,
        array $processors = [],
        LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ): static {
        $stream = @\fopen(
            filename: $file,
            mode: $append
                ? 'a'
                : 'w',
        );

        if ($stream === false) {
            throw LoggerException::fromUnableToOpenFile(
                file: $file,
            );
        }

        return new static(
            container: $container,
            stream: $stream,
            autoFlush: $autoFlush,
            formatter: $formatter,
            clock: $clock,
            processors: $processors,
            processorFailure: $processorFailure,
        );
    }

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        @\fwrite(
            $this->stream,
            $this->formatter->format($record),
        );

        if ($this->autoFlush) {
            @\fflush($this->stream);
        }
    }
}
