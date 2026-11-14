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

use Tuxxedo\Temporal\DurationInterface;

interface ClientMiddlewareInterface
{
    /**
     * @return \Generator<int, ClientRequestInterface|DurationInterface, ClientResponseInterface|HttpClientException|null, ClientResponseInterface|HttpClientException|null>
     *
     * @throws HttpClientException
     */
    public function handle(
        ClientRequestInterface $request,
    ): \Generator;
}
