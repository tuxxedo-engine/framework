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
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;

interface TransportInterface
{
    /**
     * @template TKey of array-key
     *
     * @param iterable<TKey, ClientRequestInterface> $requests
     * @param positive-int $concurrency
     * @return \Generator<TKey, ClientResponseInterface|HttpClientException>
     */
    public function send(
        iterable $requests,
        TransportOptionsInterface $options,
        int $concurrency = 1,
    ): \Generator;

    public function batch(): TransportBatchInterface;

    public function stream(
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): ClientResponseInterface|HttpClientException;
}
