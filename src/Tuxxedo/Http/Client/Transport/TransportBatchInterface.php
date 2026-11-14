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

interface TransportBatchInterface
{
    public int $pending {
        get;
    }

    public function add(
        int|string $key,
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): void;

    /**
     * @return array{0: int|string, 1: ClientResponseInterface|HttpClientException}|null
     */
    public function next(
        ?int $timeout = null,
    ): ?array;
}
