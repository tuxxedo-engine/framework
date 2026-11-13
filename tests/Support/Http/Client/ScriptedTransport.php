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

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\Transport\TransportBatchInterface;
use Tuxxedo\Http\Client\Transport\TransportInterface;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;

class ScriptedTransport implements TransportInterface
{
    /**
     * @var list<ClientRequestInterface>
     */
    public private(set) array $requests = [];

    /**
     * @var list<TransportOptionsInterface>
     */
    public private(set) array $options = [];

    /**
     * @param list<ClientResponseInterface|HttpClientException|\Closure(ClientRequestInterface): (ClientResponseInterface|HttpClientException)> $results
     */
    public function __construct(
        private array $results = [],
    ) {
    }

    public function resolve(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): ClientResponseInterface|HttpClientException {
        $this->requests[] = $request;
        $this->options[] = $options;

        $result = \array_shift($this->results);

        return match (true) {
            $result === null => HttpClientException::fromMissingTransportResult($request),
            $result instanceof \Closure => $result($request),
            default => $result,
        };
    }

    public function batch(): TransportBatchInterface
    {
        return new ScriptedTransportBatch($this);
    }

    public function stream(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): ClientResponseInterface|HttpClientException {
        return $this->resolve($request, $options);
    }

    public function send(
        iterable $requests,
        TransportOptionsInterface $options,
        int $concurrency = 1,
    ): \Generator {
        foreach ($requests as $key => $request) {
            yield $key => $this->resolve($request, $options);
        }
    }
}
