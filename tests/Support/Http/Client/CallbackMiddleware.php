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

use Tuxxedo\Http\Client\ClientMiddlewareInterface;
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Temporal\DurationInterface;

class CallbackMiddleware implements ClientMiddlewareInterface
{
    /**
     * @var list<ClientRequestInterface>
     */
    public private(set) array $seen = [];

    /**
     * @param \Closure(ClientRequestInterface): \Generator<mixed, mixed, mixed, mixed> $handler
     */
    public function __construct(
        private readonly \Closure $handler,
    ) {
    }

    public function handle(
        ClientRequestInterface $request,
    ): \Generator {
        $this->seen[] = $request;

        /** @var \Generator<int, ClientRequestInterface|DurationInterface, ClientResponseInterface|HttpClientException|null, ClientResponseInterface|HttpClientException|null> */
        return ($this->handler)($request);
    }
}
