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

use Tuxxedo\Logger\Formatter\LineFormatter;
use Tuxxedo\Logger\Formatter\LogFormatterInterface;
use Tuxxedo\Temporal\ClockInterface;

class StreamLogger extends AbstractLogger
{
    private readonly LogFormatterInterface $formatter;

    /**
     * @param resource $stream
     */
    final public function __construct(
        private readonly mixed $stream,
        public bool $autoFlush = true,
        ?LogFormatterInterface $formatter = null,
        ?ClockInterface $clock = null,
    ) {
        parent::__construct(
            clock: $clock,
        );

        $this->formatter = $formatter ?? new LineFormatter();
    }

    /**
     * @throws LoggerException
     */
    public static function createFromFile(
        string $file,
        bool $autoFlush = true,
        bool $append = true,
        ?LogFormatterInterface $formatter = null,
        ?ClockInterface $clock = null,
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
            stream: $stream,
            autoFlush: $autoFlush,
            formatter: $formatter,
            clock: $clock,
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
