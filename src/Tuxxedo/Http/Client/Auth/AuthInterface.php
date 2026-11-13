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

namespace Tuxxedo\Http\Client\Auth;

use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientInterface;

interface AuthInterface
{
    /**
     * @throws HttpClientException
     */
    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface;
}
