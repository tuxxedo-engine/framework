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

class CurlTransportBatch implements TransportBatchInterface
{
    public int $pending {
        get {
            return \sizeof($this->active) + \sizeof($this->completed);
        }
    }

    private readonly \CurlMultiHandle $multi;

    /**
     * @var array<int, array{0: int|string, 1: CurlTransfer, 2: \CurlHandle, 3: TransportOptionsInterface}>
     */
    private array $active = [];

    /**
     * @var list<array{0: int|string, 1: ClientResponseInterface|HttpClientException}>
     */
    private array $completed = [];

    /**
     * @param \Closure(ClientRequestInterface, TransportOptionsInterface): array{0: \CurlHandle, 1: CurlTransfer} $prepare
     * @param \Closure(CurlTransfer, \CurlHandle, int, TransportOptionsInterface): (ClientResponseInterface|HttpClientException) $result
     */
    public function __construct(
        private readonly \Closure $prepare,
        private readonly \Closure $result,
        int $maxHostConnections = 6,
    ) {
        $this->multi = \curl_multi_init();

        \curl_multi_setopt($this->multi, \CURLMOPT_MAX_HOST_CONNECTIONS, $maxHostConnections);
    }

    public function __destruct()
    {
        foreach ($this->active as [, , $handle]) {
            \curl_multi_remove_handle($this->multi, $handle);
        }

        \curl_multi_close($this->multi);
    }

    public function add(
        int|string $key,
        ClientRequestInterface $request,
        TransportOptionsInterface $options,
    ): void {
        [$handle, $transfer] = ($this->prepare)($request, $options);

        \curl_multi_add_handle($this->multi, $handle);

        $this->active[\spl_object_id($handle)] = [
            $key,
            $transfer,
            $handle,
            $options,
        ];
    }

    public function next(
        ?int $timeout = null,
    ): ?array {
        $deadline = $timeout !== null
            ? \microtime(true) + $timeout / 1_000_000
            : null;

        while (true) {
            if (\sizeof($this->completed) > 0) {
                return \array_shift($this->completed);
            }

            if (\sizeof($this->active) === 0) {
                return null;
            }

            do {
                $status = \curl_multi_exec($this->multi, $running);
            } while ($status === \CURLM_CALL_MULTI_PERFORM);

            while (($message = \curl_multi_info_read($this->multi)) !== false) {
                $handle = $message['handle'];
                $result = $message['result'];

                if (!$handle instanceof \CurlHandle || !\is_int($result)) {
                    continue; // @codeCoverageIgnore
                }

                [$key, $transfer, , $options] = $this->active[\spl_object_id($handle)];

                unset($this->active[\spl_object_id($handle)]);
                \curl_multi_remove_handle($this->multi, $handle);

                $this->completed[] = [
                    $key,
                    ($this->result)($transfer, $handle, $result, $options),
                ];
            }

            if (\sizeof($this->completed) > 0) {
                continue;
            }

            $remaining = $deadline !== null
                ? $deadline - \microtime(true)
                : 1.0;

            if ($remaining <= 0) {
                return null;
            }

            if ($running > 0) {
                \curl_multi_select($this->multi, \min(1.0, $remaining));
            }
        }
    }
}
