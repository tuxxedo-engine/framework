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

namespace Tuxxedo\Http\Response\Stream;

class ResourceStreamProxy implements StreamProxyInterface
{
    /**
     * @param resource $resource
     * @param positive-int $chunkSize
     */
    public function __construct(
        private mixed $resource,
        private int $chunkSize = 8192,
    ) {
    }

    public function eof(): bool
    {
        return \feof($this->resource);
    }

    public function getSize(): ?int
    {
        $stat = @\fstat($this->resource);

        if ($stat !== false && $stat['size'] > 0) {
            return $stat['size'];
        }

        return null;
    }

    public function read(): ?string
    {
        $data = \fread($this->resource, $this->chunkSize);

        return $data === '' || $data === false
            ? null
            : $data;
    }

    public function contents(): string
    {
        \rewind($this->resource);

        $contents = \stream_get_contents($this->resource);

        return $contents !== false
            ? $contents
            : '';
    }
}
