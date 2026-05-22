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

use Tuxxedo\Http\Header;
use Tuxxedo\Http\Response\PrefersHeadersInterface;

class JsonStreamProxy implements StreamProxyInterface, PrefersHeadersInterface
{
    /**
     * @var \Generator<mixed>
     */
    private \Generator $inner;
    private bool $done = false;

    public readonly array $headers;

    /**
     * @param \Closure(): \Generator<mixed>|\Generator<mixed> $generator
     */
    public function __construct(
        \Closure|\Generator $generator,
        private readonly JsonStreamFormat $format = JsonStreamFormat::JSONL,
    ) {
        $this->inner = $generator instanceof \Closure
            ? $generator()
            : $generator;

        $this->headers = [
             new Header('Content-Type', $this->format->getContentType()),
        ];
    }

    public function eof(): bool
    {
        return $this->done;
    }

    public function getSize(): null
    {
        return null;
    }

    public function read(): ?string
    {
        if ($this->done || !$this->inner->valid()) {
            $this->done = true;

            return null;
        }

        $current = $this->inner->current();

        $this->inner->next();

        if (!$this->inner->valid()) {
            $this->done = true;
        }

        $encoded = \json_encode($current, \JSON_THROW_ON_ERROR);

        return match ($this->format) {
            JsonStreamFormat::JSONL => $encoded . "\n",
            JsonStreamFormat::RFC7464 => "\x1e" . $encoded . "\n",
        };
    }

    public function contents(): string
    {
        $buffer = '';

        while (!$this->eof()) {
            $chunk = $this->read();

            if ($chunk === null) {
                break;
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }
}
