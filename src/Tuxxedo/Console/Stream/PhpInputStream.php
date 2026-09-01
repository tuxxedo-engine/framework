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

namespace Tuxxedo\Console\Stream;

use Tuxxedo\Console\ConsoleException;

class PhpInputStream implements InputStreamInterface
{
    public bool $isTerminal {
        get {
            if (!\is_resource($this->resource)) {
                return false;
            }

            return \stream_isatty($this->resource);
        }
    }

    /**
     * @param resource $resource
     */
    public function __construct(
        private mixed $resource,
    ) {
    }

    /**
     * @param positive-int $length
     */
    public function read(
        int $length,
    ): string {
        $resource = $this->requireResource();
        $bytes = \fread($resource, $length);

        if ($bytes === false) {
            throw ConsoleException::fromStreamReadFailure();
        }

        return $bytes;
    }

    public function readLine(): ?string
    {
        $resource = $this->requireResource();
        $line = \fgets($resource);

        if ($line === false) {
            if (\feof($resource)) {
                return null;
            }

            throw ConsoleException::fromStreamReadFailure();
        }

        return \rtrim($line, "\r\n");
    }

    public function readAll(): string
    {
        $resource = $this->requireResource();
        $contents = \stream_get_contents($resource);

        if ($contents === false) {
            throw ConsoleException::fromStreamReadFailure();
        }

        return $contents;
    }

    public function close(): void
    {
        if (!\is_resource($this->resource)) {
            return;
        }

        @\fclose($this->resource);
    }

    /**
     * @return resource
     *
     * @throws ConsoleException
     */
    private function requireResource(): mixed
    {
        if (!\is_resource($this->resource)) {
            throw ConsoleException::fromStreamNotOpen();
        }

        return $this->resource;
    }
}
