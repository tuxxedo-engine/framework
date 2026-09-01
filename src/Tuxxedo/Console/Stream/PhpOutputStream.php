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

class PhpOutputStream implements OutputStreamInterface
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

    public function write(
        string $bytes,
    ): void {
        $resource = $this->requireResource();
        $remaining = $bytes;

        while ($remaining !== '') {
            $written = \fwrite($resource, $remaining);

            if ($written === false || $written === 0) {
                throw ConsoleException::fromStreamWriteFailure();
            }

            $remaining = \substr($remaining, $written);
        }
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
