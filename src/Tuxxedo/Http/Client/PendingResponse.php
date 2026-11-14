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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Client\Transport\TransportInterface;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;

/**
 * @template T
 *
 * @implements PendingResponseInterface<T>
 */
class PendingResponse implements PendingResponseInterface
{
    public private(set) bool $completed = false;

    public bool $succeeded {
        get {
            return $this->completed && $this->exception === null;
        }
    }

    public private(set) ?\Throwable $exception = null;

    /**
     * @var T|null
     */
    private mixed $value = null;

    /**
     * @param \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface> $pipeline
     * @param \Closure(ClientResponseInterface): T $decode
     * @param (\Closure(): void)|null $failed
     */
    public function __construct(
        public readonly TransportInterface $transport,
        public readonly TransportOptionsInterface $options,
        private readonly \Generator $pipeline,
        private readonly \Closure $decode,
        private readonly ?\Closure $failed = null,
    ) {
    }

    /**
     * @return \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface>
     */
    public function start(): \Generator
    {
        return $this->pipeline;
    }

    public function settle(
        ClientResponseInterface|\Throwable $outcome,
    ): void {
        if ($outcome instanceof \Throwable) {
            $this->exception = $outcome;
        } else {
            try {
                $this->value = ($this->decode)($outcome);
            } catch (\Throwable $exception) {
                $this->exception = $exception;
            }
        }

        if ($this->exception !== null && $this->failed !== null) {
            ($this->failed)();
        }

        $this->completed = true;
    }

    public function result(): mixed
    {
        if (!$this->completed) {
            \iterator_to_array(
                ClientScheduler::run(
                    [
                        $this,
                    ],
                    1,
                ),
            );
        }

        if ($this->exception !== null) {
            throw $this->exception;
        }

        /** @var T */
        return $this->value;
    }
}
