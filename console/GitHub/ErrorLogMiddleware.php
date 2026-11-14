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

namespace Console\GitHub;

use Tuxxedo\Http\Client\ClientMiddlewareInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Logger\LoggerInterface;

class ErrorLogMiddleware implements ClientMiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(
        ClientRequestInterface $request,
    ): \Generator {
        $outcome = yield $request;

        if ($outcome instanceof HttpClientException) {
            $this->logger->error(
                message: '{method} {url} failed: {error}',
                context: [
                    'method' => $request->method->name,
                    'url' => (string) $request->url,
                    'error' => $outcome->getMessage(),
                ],
                exception: $outcome,
            );
        } elseif ($outcome !== null && $outcome->statusCode >= 400) {
            $this->logger->warning(
                message: '{method} {url} responded with {status}',
                context: [
                    'method' => $request->method->name,
                    'url' => (string) $request->url,
                    'status' => $outcome->statusCode,
                ],
            );
        }

        return $outcome;
    }
}
