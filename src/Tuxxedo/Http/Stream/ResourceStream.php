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

namespace Tuxxedo\Http\Stream;

class ResourceStream implements ResourceStreamInterface
{
    /**
     * @var resource|null
     */
    private mixed $resource;

    public bool $closed {
        get {
            return $this->resource === null;
        }
    }

    public array $headers {
        get {
            return [];
        }
    }

    /**
     * @param resource $resource
     * @param positive-int $chunkSize
     */
    final public function __construct(
        mixed $resource,
        public readonly int $chunkSize = 8192,
        public readonly bool $autoFlush = false,
    ) {
        $this->resource = $resource;
    }

    /**
     * @param positive-int $chunkSize
     *
     * @throws StreamException
     */
    public static function fromFile(
        string $path,
        int $chunkSize = 8192,
        bool $autoFlush = false,
    ): static {
        $resource = @\fopen($path, 'rb');

        if ($resource === false) {
            throw StreamException::fromUnreadableFile($path);
        }

        return new static(
            resource: $resource,
            chunkSize: $chunkSize,
            autoFlush: $autoFlush,
        );
    }

    /**
     * @param resource $resource
     * @param positive-int $chunkSize
     */
    public static function fromResource(
        mixed $resource,
        int $chunkSize = 8192,
        bool $autoFlush = false,
    ): static {
        return new static(
            resource: $resource,
            chunkSize: $chunkSize,
            autoFlush: $autoFlush,
        );
    }

    /**
     * @param positive-int $chunkSize
     *
     * @throws StreamException
     */
    public static function fromTemporary(
        ?int $maxMemory = 1024 * 1024 * 2,
        int $chunkSize = 8192,
        bool $autoFlush = false,
    ): static {
        $resource = @\fopen(
            $maxMemory !== null
                ? \sprintf(
                    'php://temp/maxmemory:%d',
                    $maxMemory,
                )
                : 'php://temp',
            'r+b',
        );

        if ($resource === false) {
            throw StreamException::fromTemporaryStream(); // @codeCoverageIgnore
        }

        return new static(
            resource: $resource,
            chunkSize: $chunkSize,
            autoFlush: $autoFlush,
        );
    }

    public function close(): void
    {
        $this->resource = null;
    }

    public function eof(): bool
    {
        return $this->resource === null || \feof($this->resource);
    }

    public function getSize(): ?int
    {
        $stat = $this->resource !== null
            ? @\fstat($this->resource)
            : false;

        if ($stat !== false && (($stat['mode'] & 0o170000) === 0o100000 || $stat['size'] > 0)) {
            return $stat['size'];
        }

        return null;
    }

    public function read(): ?string
    {
        if ($this->resource === null) {
            return null;
        }

        $data = \fread($this->resource, $this->chunkSize);

        return $data === '' || $data === false
            ? null
            : $data;
    }

    public function getContents(): string
    {
        if ($this->resource === null) {
            return '';
        }

        $contents = \stream_get_contents($this->resource);

        return $contents !== false
            ? $contents
            : '';
    }

    public function write(
        string $data,
    ): void {
        if ($this->resource === null) {
            throw StreamException::fromClosedStream();
        }

        $written = @\fwrite($this->resource, $data);

        if ($written === false || $written < \strlen($data)) {
            throw StreamException::fromWriteFailure();
        }
    }

    public function rewind(): void
    {
        if ($this->resource === null) {
            throw StreamException::fromClosedStream();
        }

        if (!@\rewind($this->resource)) {
            throw StreamException::fromUnseekableStream();
        }
    }

    /**
     * @throws StreamException
     */
    public function copyTo(
        string $path,
    ): void {
        $target = @\fopen($path, 'wb');

        if ($target === false) {
            throw StreamException::fromUnwritableFile($path);
        }

        try {
            while (($chunk = $this->read()) !== null) {
                \fwrite($target, $chunk);
            }
        } finally {
            \fclose($target);
        }
    }
}
