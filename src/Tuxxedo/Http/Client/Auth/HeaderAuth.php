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
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Header;

readonly class HeaderAuth implements AuthInterface
{
    public function __construct(
        private string $name,
        #[\SensitiveParameter] private string $value,
    ) {
    }

    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface {
        return $request->withHeader(
            header: new Header($this->name, $this->value),
            replace: true,
        );
    }
}
