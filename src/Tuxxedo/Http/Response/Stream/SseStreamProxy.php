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

class SseStreamProxy implements StreamProxyInterface, PrefersHeadersInterface
{
    /**
     * @var \Generator<SseEventInterface>
     */
    private \Generator $inner;
    private bool $done = false;

    public readonly array $headers;

    /**
     * @param \Closure(): \Generator<SseEventInterface>|\Generator<SseEventInterface> $generator
     */
    public function __construct(
        \Closure|\Generator $generator,
    ) {
        $this->inner = $generator instanceof \Closure
            ? $generator()
            : $generator;

        $this->headers = [
            new Header('Content-Type', 'text/event-stream'),
            new Header('Cache-Control', 'no-cache'),
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

        $event = $this->inner->current();

        $this->inner->next();

        if (!$this->inner->valid()) {
            $this->done = true;
        }

        return $this->formatEvent($event);
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

    private function formatEvent(
        SseEventInterface $event,
    ): string {
        if ($event->comment !== null) {
            return ': ' . $event->comment . "\n\n";
        }

        $buffer = '';

        if ($event->id !== null) {
            $buffer .= 'id: ' . $event->id . "\n";
        }

        if ($event->event !== null) {
            $buffer .= 'event: ' . $event->event . "\n";
        }

        if ($event->retry !== null) {
            $buffer .= 'retry: ' . $event->retry . "\n";
        }

        $buffer .= 'data: ' . $event->data . "\n";

        return $buffer . "\n";
    }
}
