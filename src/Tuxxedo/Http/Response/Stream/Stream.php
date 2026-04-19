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

use Tuxxedo\Http\HttpException;

class Stream implements StreamInterface
{
    private ?StreamProxyInterface $streamProxy;

    public bool $closed {
        get {
            return $this->streamProxy === null;
        }
    }

    final public function __construct(
        StreamProxyInterface $streamProxy,
        public readonly bool $autoFlush = false,
    ) {
        $this->streamProxy = $streamProxy;
    }

    /**
     * @param \Closure(): \Generator<string>|\Generator<string> $generator
     */
    public static function fromGenerator(
        \Closure|\Generator $generator,
        bool $autoFlush = true,
    ): static {
        return new static(
            streamProxy: new GeneratorStreamProxy(
                generator: $generator,
            ),
            autoFlush: $autoFlush,
        );
    }

    /**
     * @param positive-int $chunkSize
     *
     * @throws HttpException
     */
    public static function fromFile(
        string $path,
        int $chunkSize = 8192,
        bool $autoFlush = false,
    ): static {
        $resource = @\fopen($path, 'rb');

        if ($resource === false) {
            throw HttpException::fromInternalServerError();
        }

        return new static(
            streamProxy: new ResourceStreamProxy(
                resource: $resource,
                chunkSize: $chunkSize,
            ),
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
            streamProxy: new ResourceStreamProxy(
                resource: $resource,
                chunkSize: $chunkSize,
            ),
            autoFlush: $autoFlush,
        );
    }

    /**
     * @param int|null $maxMemory
     * @param positive-int $chunkSize
     *
     * @throws HttpException
     */
    public static function fromTemporary(
        ?int $maxMemory = 1024 * 1024 * 2,
        bool $autoFlush = false,
        int $chunkSize = 8192,
    ): static {
        if ($maxMemory !== null) {
            $resource = @\fopen(
                \sprintf(
                    'php://temp/maxmemory:%d',
                    $maxMemory,
                ),
                'r+b',
            );
        } else {
            $resource = @\fopen('php://temp', 'r+b');
        }

        if ($resource === false) {
            /**
             * @codeCoverageIgnore
             */
            throw HttpException::fromInternalServerError();
        }

        return new static(
            streamProxy: new ResourceStreamProxy(
                resource: $resource,
                chunkSize: $chunkSize,
            ),
            autoFlush: $autoFlush,
        );
    }

    public function close(): void
    {
        $this->streamProxy = null;
    }

    public function eof(): bool
    {
        return $this->streamProxy?->eof() ?? true;
    }

    public function getSize(): ?int
    {
        return $this->streamProxy?->getSize() ?? null;
    }

    public function read(): ?string
    {
        return $this->streamProxy?->read() ?? null;
    }

    public function getContents(): string
    {
        return $this->streamProxy?->contents() ?? '';
    }
}
