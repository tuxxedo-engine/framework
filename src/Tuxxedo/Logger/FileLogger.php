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

class FileLogger extends AbstractLogger implements FileLoggerInterface
{
    /**
     * @var resource
     */
    private $filePointer;

    /**
     * @throws LoggerException
     */
    public function __construct(
        public readonly string $file,
        public readonly bool $autoFlush = true,
        public readonly bool $append = true,
    ) {
        $filePointer = @\fopen(
            filename: $this->file,
            mode: $this->append
                ? 'a'
                : 'w',
        );

        if ($filePointer === false) {
            throw LoggerException::fromUnableToOpenFile(
                file: $this->file,
            );
        }

        $this->filePointer = $filePointer;
    }

    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static {
        @\fwrite(
            $this->filePointer,
            \sprintf(
                '[%s] %s%s',
                $level->name,
                parent::interpolate($message),
                \PHP_EOL,
            ),
        );

        if ($this->autoFlush) {
            @\fflush($this->filePointer);
        }

        parent::incrementByLogLevel($level);

        return $this;
    }
}
