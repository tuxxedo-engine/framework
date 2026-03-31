<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Logger;

class StreamLogger extends AbstractLogger
{
    /**
     * @param resource $stream
     */
    final public function __construct(
        private readonly mixed $stream,
        public bool $autoFlush = true,
        ?LogMessageFormatter $formatter = null,
    ) {
        parent::__construct(
            formatter: $formatter,
        );
    }

    /**
     * @throws LoggerException
     */
    public static function createFromFile(
        string $file,
        bool $autoFlush = true,
        bool $append = true,
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
        );
    }

    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static {
        @\fwrite(
            $this->stream,
            $this->formatter->format($message, $placeholders, $level),
        );

        if ($this->autoFlush) {
            @\fflush($this->stream);
        }

        parent::incrementByLogLevel($level);

        return $this;
    }
}
