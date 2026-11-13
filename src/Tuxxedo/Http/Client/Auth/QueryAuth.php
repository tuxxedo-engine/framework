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

readonly class QueryAuth implements AuthInterface
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
        $name = \rawurlencode($this->name);
        $pairs = [];

        foreach (\explode('&', $request->url->query) as $pair) {
            if ($pair !== '' && $pair !== $name && !\str_starts_with($pair, $name . '=')) {
                $pairs[] = $pair;
            }
        }

        $pairs[] = $name . '=' . \rawurlencode($this->value);

        return $request->withUrl(
            $request->url->withQuery(\join('&', $pairs)),
        );
    }
}
