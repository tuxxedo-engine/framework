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
use Tuxxedo\Http\Client\Transport\TransportBatchInterface;

class ClientScheduler
{
    /**
     * @template TKey of array-key
     * @template TPending of PendingResponseInterface<mixed>
     *
     * @param iterable<TKey, TPending> $pending
     * @return \Generator<TKey, TPending>
     */
    public static function run(
        iterable $pending,
        int $concurrency,
    ): \Generator {
        $source = (static fn (): \Generator => yield from $pending)();

        /** @var array<int, array{0: TKey, 1: TPending, 2: \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface>, 3: int, 4: PendingResponse<mixed>}> $running */
        $running = [];

        /** @var array<int, TransportBatchInterface> $batches */
        $batches = [];

        /** @var array<int, float> $timers */
        $timers = [];

        $settled = [];
        $nextId = 0;

        while (true) {
            while (\sizeof($running) < \max(1, $concurrency) && $source->valid()) {
                $key = $source->key();
                $item = $source->current();

                $source->next();

                if ($item->completed || !$item instanceof PendingResponse) {
                    yield $key => $item;

                    continue;
                }

                $transport = \spl_object_id($item->transport);
                $batches[$transport] ??= $item->transport->batch();
                $id = $nextId++;
                $running[$id] = [
                    $key,
                    $item,
                    $item->start(),
                    $transport,
                    $item,
                ];

                self::step($id, $running, $batches, $timers, $settled, null, false);
            }

            foreach ($settled as [$key, $item]) {
                yield $key => $item;
            }

            $settled = [];

            if (\sizeof($running) === 0) {
                return;
            }

            $now = \microtime(true);

            foreach ($timers as $id => $due) {
                if ($due <= $now) {
                    unset($timers[$id]);

                    self::step($id, $running, $batches, $timers, $settled, null, true);
                }
            }

            $timeout = \sizeof($timers) > 0
                ? (int) \max(0, (\min($timers) - \microtime(true)) * 1_000_000)
                : null;

            $active = \array_filter(
                $batches,
                static fn (TransportBatchInterface $batch): bool => $batch->pending > 0,
            );

            if (\sizeof($active) === 0) {
                \usleep((int) $timeout);

                continue;
            }

            foreach ($active as $batch) {
                $result = $batch->next(
                    \sizeof($active) > 1
                        ? 0
                        : $timeout,
                );

                if ($result !== null) {
                    self::step((int) $result[0], $running, $batches, $timers, $settled, $result[1], true);
                }
            }

            if (\sizeof($active) > 1 && \sizeof($settled) === 0) {
                \usleep(1_000);
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TPending of PendingResponseInterface<mixed>
     *
     * @param array<int, array{0: TKey, 1: TPending, 2: \Generator<int, ClientRequestInterface|int, ClientResponseInterface|HttpClientException|null, ClientResponseInterface>, 3: int, 4: PendingResponse<mixed>}> $running
     * @param array<int, TransportBatchInterface> $batches
     * @param array<int, float> $timers
     * @param list<array{0: TKey, 1: TPending}> $settled
     */
    private static function step(
        int $id,
        array &$running,
        array $batches,
        array &$timers,
        array &$settled,
        ClientResponseInterface|HttpClientException|null $result,
        bool $resume,
    ): void {
        [$key, $item, $pipeline, $transport, $runner] = $running[$id];

        try {
            if ($resume) {
                $pipeline->send($result);
            }

            if (!$pipeline->valid()) {
                $runner->settle($pipeline->getReturn());
            } else {
                $step = $pipeline->current();

                if (\is_int($step)) {
                    $timers[$id] = \microtime(true) + $step / 1_000_000;
                } else {
                    $batches[$transport]->add($id, $step, $runner->options);
                }

                return;
            }
        } catch (\Throwable $exception) {
            $runner->settle($exception);
        }

        unset($running[$id]);

        $settled[] = [
            $key,
            $item,
        ];
    }
}
