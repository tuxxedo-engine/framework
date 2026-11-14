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

namespace Support\Http\Client;

use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Transport\TransportBatchInterface;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;

class ScriptedTransportBatch implements TransportBatchInterface
{
    public int $pending {
        get {
            return \sizeof($this->queue);
        }
    }

    /**
     * @var list<array{0: int|string, 1: ClientRequestInterface, 2: TransportOptionsInterface}>
     */
    private array $queue = [];

    public function __construct(
        private readonly ScriptedTransport $transport,
    ) {
    }

    public function add(
        int|string $key,
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): void {
        $this->queue[] = [
            $key,
            $request,
            $options,
        ];
    }

    public function next(
        ?int $timeout = null,
    ): ?array {
        $next = \array_shift($this->queue);

        if ($next === null) {
            return null;
        }

        return [
            $next[0],
            $this->transport->resolve($next[1], $next[2]),
        ];
    }
}
