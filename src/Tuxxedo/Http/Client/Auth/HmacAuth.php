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
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Stream\ResourceStreamInterface;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\SystemClock;

readonly class HmacAuth implements AuthInterface
{
    private ClockInterface $clock;

    public function __construct(
        private SerializerManagerInterface $serializerManager,
        #[\SensitiveParameter] private string $key,
        private string $algorithm = 'sha256',
        private string $signatureHeader = 'X-Signature',
        private string $timestampHeader = 'X-Timestamp',
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface {
        $timestamp = (string) $this->clock->now()->toUnixTimestamp();
        $target = $request->url->path !== ''
            ? $request->url->path
            : '/';

        if ($request->url->query !== '') {
            $target .= '?' . $request->url->query;
        }

        $payload = \join(
            "\n",
            [
                $request->method->name,
                $target,
                $timestamp,
                \hash($this->algorithm, $this->body($request)),
            ],
        );

        return $request
            ->withHeader(
                header: new Header($this->timestampHeader, $timestamp),
                replace: true,
            )
            ->withHeader(
                header: new Header($this->signatureHeader, \hash_hmac($this->algorithm, $payload, $this->key)),
                replace: true,
            );
    }

    /**
     * @throws HttpClientException
     */
    private function body(
        ClientRequestInterface $request,
    ): string {
        $body = $request->body?->encode($this->serializerManager);

        if ($body === null || \is_string($body)) {
            return $body ?? '';
        }

        if (!$body instanceof ResourceStreamInterface) {
            throw HttpClientException::fromUnrewindableBody($request);
        }

        $body->rewind();

        $contents = $body->getContents();

        $body->rewind();

        return $contents;
    }
}
