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

namespace Unit\Http\Client\Transport;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\Transport\CurlBodyStreamProxy;
use Tuxxedo\Http\Client\Transport\CurlTransfer;
use Tuxxedo\Http\Method;

#[RequiresPhpExtension('curl')]
class CurlBodyStreamProxyTest extends TestCase
{
    /**
     * @param list<string|null> $chunks
     */
    private static function proxy(
        array $chunks,
        ?HttpClientException $failure = null,
    ): CurlBodyStreamProxy {
        $handle = \curl_init();
        $transfer = new CurlTransfer(
            request: new ClientRequest(Method::GET, 'https://api.example/'),
        );

        return new CurlBodyStreamProxy(
            multi: \curl_multi_init(),
            handle: $handle,
            transfer: $transfer,
            pump: static function () use (&$chunks, $handle, $transfer): ?int {
                $chunk = \array_shift($chunks);

                if ($chunk !== null) {
                    $transfer->write($handle, $chunk);
                }

                return \sizeof($chunks) === 0
                    ? \CURLE_OK
                    : null;
            },
            failureOf: static fn (int $result): ?HttpClientException => $failure,
        );
    }

    public function testContentsPumpsUntilTheTransferIsDone(): void
    {
        $proxy = self::proxy(
            [
                'a',
                null,
                'b',
                'c',
            ],
        );

        self::assertFalse($proxy->eof());
        self::assertNull($proxy->getSize());
        self::assertSame('abc', $proxy->contents());
        self::assertTrue($proxy->eof());
        self::assertNull($proxy->read());
    }

    public function testFailuresArriveAfterTheBufferedData(): void
    {
        $proxy = self::proxy(
            [
                'a',
                'b',
            ],
            HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/'), HttpClientTimeout::IDLE),
        );
        $chunks = [
            $proxy->read(),
            $proxy->read(),
        ];

        try {
            $proxy->read();

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::TIMEOUT, $exception->failure);
        }

        self::assertSame(
            [
                'a',
                'b',
            ],
            $chunks,
        );
        self::assertNull($proxy->read());
    }
}
