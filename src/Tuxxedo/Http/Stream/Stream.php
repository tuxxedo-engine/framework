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

use Tuxxedo\Http\Response\PrefersHeadersInterface;

class Stream implements StreamInterface
{
    private ?StreamProxyInterface $streamProxy;

    public bool $closed {
        get {
            return $this->streamProxy === null;
        }
    }

    public readonly array $headers;

    final public function __construct(
        StreamProxyInterface $streamProxy,
        public readonly bool $autoFlush = false,
    ) {
        $this->streamProxy = $streamProxy;
        $this->headers = $streamProxy instanceof PrefersHeadersInterface
            ? $streamProxy->headers
            : [];
    }

    /**
     * @param \Closure(): \Generator<string>|\Generator<string> $generator
     */
    public static function fromGenerator(
        \Closure|\Generator $generator,
        bool $autoFlush = true,
        ?int $size = null,
    ): static {
        return new static(
            streamProxy: new GeneratorStreamProxy(
                generator: $generator,
                size: $size,
            ),
            autoFlush: $autoFlush,
        );
    }

    /**
     * @param \Closure(): \Generator<mixed>|\Generator<mixed> $generator
     */
    public static function fromJson(
        \Closure|\Generator $generator,
        JsonStreamFormat $format = JsonStreamFormat::JSONL,
    ): static {
        return new static(
            streamProxy: new JsonStreamProxy(
                generator: $generator,
                format: $format,
            ),
            autoFlush: true,
        );
    }

    /**
     * @param \Closure(): \Generator<scalar[]>|\Generator<scalar[]> $generator
     * @param string[]|null $columns
     */
    public static function fromCsv(
        \Closure|\Generator $generator,
        string $separator = ',',
        string $enclosure = '"',
        string $eol = "\n",
        ?array $columns = null,
    ): static {
        return new static(
            streamProxy: new CsvStreamProxy(
                generator: $generator,
                separator: $separator,
                enclosure: $enclosure,
                eol: $eol,
                columns: $columns,
            ),
            autoFlush: true,
        );
    }

    /**
     * @param \Closure(): \Generator<SseEventInterface>|\Generator<SseEventInterface> $generator
     */
    public static function fromSse(
        \Closure|\Generator $generator,
    ): static {
        return new static(
            streamProxy: new SseStreamProxy(
                generator: $generator,
            ),
            autoFlush: true,
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
        return $this->streamProxy?->getSize();
    }

    public function read(): ?string
    {
        return $this->streamProxy?->read();
    }

    public function getContents(): string
    {
        return $this->streamProxy?->contents() ?? '';
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
