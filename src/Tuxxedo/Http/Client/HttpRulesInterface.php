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

interface HttpRulesInterface
{
    /**
     * @throws HttpClientException
     */
    public function evaluate(
        ClientRequestInterface $request,
    ): ClientResponseInterface|HttpClientException|null;

    public function allowsAddress(
        string $address,
    ): bool;
}
