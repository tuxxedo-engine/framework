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

namespace Tuxxedo\Http\Client\Transport;

use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Stream\StreamProxyInterface;

class CurlBodyStreamProxy implements StreamProxyInterface
{
    private string $buffer = '';
    private bool $done = false;
    private ?HttpClientException $failure = null;
    private bool $released = false;

    /**
     * @param \Closure(): ?int $pump
     * @param \Closure(int): ?HttpClientException $failureOf
     */
    public function __construct(
        private readonly \CurlMultiHandle $multi,
        private readonly \CurlHandle $handle,
        private readonly CurlTransfer $transfer,
        private readonly \Closure $pump,
        private readonly \Closure $failureOf,
    ) {
    }

    public function __destruct()
    {
        $this->release();
    }

    public function eof(): bool
    {
        return $this->done && $this->buffer === '' && $this->failure === null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function read(): ?string
    {
        $this->buffer .= $this->transfer->drain();

        while ($this->buffer === '' && !$this->done) {
            $this->advance();
        }

        if ($this->buffer !== '') {
            $chunk = $this->buffer;
            $this->buffer = '';

            return $chunk;
        }

        if ($this->failure !== null) {
            $failure = $this->failure;
            $this->failure = null;

            throw $failure;
        }

        return null;
    }

    public function contents(): string
    {
        $contents = '';

        while (($chunk = $this->read()) !== null) {
            $contents .= $chunk;
        }

        return $contents;
    }

    private function advance(): void
    {
        $result = ($this->pump)();

        $this->buffer .= $this->transfer->drain();

        if ($result === null) {
            return;
        }

        $this->done = true;
        $this->failure = ($this->failureOf)($result);

        $this->release();
    }

    private function release(): void
    {
        if ($this->released) {
            return;
        }

        $this->released = true;

        \curl_multi_remove_handle($this->multi, $this->handle);
        \curl_multi_close($this->multi);
    }
}
