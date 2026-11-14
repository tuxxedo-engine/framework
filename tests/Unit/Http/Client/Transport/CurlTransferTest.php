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
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\ClientProgressInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Transport\CurlTransfer;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\ResourceStream;

#[RequiresPhpExtension('curl')]
class CurlTransferTest extends TestCase
{
    private static function transfer(
        ?ResourceStream $upload = null,
        ?int $maxResponseSize = null,
        ?\Closure $addressCheck = null,
    ): CurlTransfer {
        return new CurlTransfer(
            request: new ClientRequest(Method::GET, 'https://api.example/users'),
            upload: $upload,
            maxResponseSize: $maxResponseSize,
            addressCheck: $addressCheck,
        );
    }

    public function testHeadersOfTheFinalResponseAreKept(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer();

        foreach (
            [
                "HTTP/1.1 100 Continue\r\n",
                "\r\n",
                "HTTP/2 201\r\n",
                "content-type: application/json\r\n",
                "x-id: 7\r\n",
                "\r\n",
            ] as $line
        ) {
            self::assertSame(\strlen($line), $transfer->header($handle, $line));
        }

        self::assertSame(\strlen('{"id":7}'), $transfer->write($handle, '{"id":7}'));

        $response = $transfer->toResponse(
            info: [
                'url' => 'https://api.example/users',
                'total_time_us' => 10,
            ],
            serializerManager: SerializerManagerFactory::create(),
        );

        self::assertSame(201, $response->statusCode);
        self::assertSame(HttpVersion::V2_0, $response->httpVersion);
        self::assertSame('7', $response->headers->string('X-Id'));
        self::assertSame('{"id":7}', $response->body->getRaw());
        self::assertTrue($response->body->isJson());
        self::assertSame('/users', $response->info->effectiveUrl->path);
    }

    public function testHeadersCompleteAfterTheFinalHeaderBlock(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer();
        $states = [];

        foreach (
            [
                "HTTP/1.1 100 Continue\r\n",
                "\r\n",
                "HTTP/1.1 200 OK\r\n",
                "\r\n",
            ] as $line
        ) {
            $transfer->header($handle, $line);

            $states[] = $transfer->headersComplete;
        }

        self::assertSame(
            [
                false,
                false,
                false,
                true,
            ],
            $states,
        );
    }

    public function testWritingCompletesHeadersAndDrainEmptiesTheBuffer(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer();

        $transfer->header($handle, "HTTP/1.1 200 OK\r\n");
        $transfer->write($handle, 'ab');

        $complete = $transfer->headersComplete;
        $first = $transfer->drain();
        $second = $transfer->drain();
        $response = $transfer->toResponse(
            info: [],
            serializerManager: SerializerManagerFactory::create(),
            content: ResourceStream::fromTemporary(),
        );

        self::assertTrue($complete);
        self::assertSame('ab', $first);
        self::assertSame('', $second);
        self::assertSame('', $response->body->getRaw());
    }

    public function testProgressReportsChangesAndCanAbort(): void
    {
        $handle = \curl_init();
        $reports = [];
        $transfer = new CurlTransfer(
            request: new ClientRequest(Method::GET, 'https://api.example/users'),
            progress: static function (ClientProgressInterface $progress) use (&$reports): bool {
                $reports[] = [
                    $progress->downloaded,
                    $progress->downloadTotal,
                    $progress->uploaded,
                    $progress->uploadTotal,
                ];

                return $progress->downloaded < 10;
            },
        );
        $results = [
            $transfer->progress($handle, 0, 0, 0, 0),
            $transfer->progress($handle, 0, 0, 0, 0),
            $transfer->progress($handle, 20, 5, 4, 4),
            $transfer->progress($handle, 20, 10, 4, 4),
        ];

        self::assertSame(
            [
                0,
                0,
                0,
                1,
            ],
            $results,
        );
        self::assertSame(
            [
                [
                    0,
                    null,
                    0,
                    null,
                ],
                [
                    5,
                    20,
                    4,
                    4,
                ],
                [
                    10,
                    20,
                    4,
                    4,
                ],
            ],
            $reports,
        );
        self::assertTrue($transfer->aborted);
        self::assertSame(0, self::transfer()->progress($handle, 1, 1, 1, 1));
    }

    public function testSinkWritesToTheFileAndHonoursTheCap(): void
    {
        $handle = \curl_init();
        $path = \sys_get_temp_dir() . '/tuxxedo-sink-' . \bin2hex(\random_bytes(4));
        $transfer = new CurlTransfer(
            request: new ClientRequest(Method::GET, 'https://api.example/file'),
            maxResponseSize: 5,
            sink: $path,
        );

        try {
            $written = [
                $transfer->write($handle, 'abc'),
                $transfer->write($handle, 'de'),
                $transfer->write($handle, 'f'),
            ];

            $transfer->close();
            $transfer->close();

            self::assertSame(
                [
                    3,
                    2,
                    0,
                ],
                $written,
            );
            self::assertSame('abcde', \file_get_contents($path));
            self::assertSame('', $transfer->drain());
            self::assertTrue($transfer->tooLarge);
            self::assertFalse($transfer->unwritable);
            self::assertSame(0, $transfer->write($handle, 'x'));
        } finally {
            @\unlink($path);
        }
    }

    public function testAnUnwritableSinkRefusesData(): void
    {
        $transfer = new CurlTransfer(
            request: new ClientRequest(Method::GET, 'https://api.example/file'),
            sink: \sys_get_temp_dir() . '/missing-' . \bin2hex(\random_bytes(4)) . '/file',
        );

        self::assertTrue($transfer->unwritable);
        self::assertSame(0, $transfer->write(\curl_init(), 'abc'));
    }

    public function testWriteStopsAtTheSizeCap(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer(maxResponseSize: 5);

        $written = $transfer->write($handle, 'abcd');
        $tooLargeBefore = $transfer->tooLarge;
        $rejected = $transfer->write($handle, 'ef');

        self::assertSame(4, $written);
        self::assertFalse($tooLargeBefore);
        self::assertSame(0, $rejected);
        self::assertTrue($transfer->tooLarge);
    }

    public function testReadServesTheUploadInRequestedLengths(): void
    {
        $handle = \curl_init();
        $upload = ResourceStream::fromTemporary(chunkSize: 4);

        $upload->write('abcdefghij');
        $upload->rewind();

        $transfer = self::transfer(upload: $upload);

        self::assertSame(
            [
                'abc',
                'defghij',
                '',
            ],
            [
                $transfer->read($handle, null, 3),
                $transfer->read($handle, null, 100),
                $transfer->read($handle, null, 100),
            ],
        );

        self::assertSame('', self::transfer()->read($handle, null, 100));
    }

    public function testPrerequestChecksTheAddress(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer(
            addressCheck: static fn (string $ip): bool => $ip !== '10.0.0.1',
        );

        $allowed = $transfer->prerequest($handle, '192.0.2.1', '192.0.2.9', 443, 50000);
        $deniedBefore = $transfer->deniedAddress;
        $denied = $transfer->prerequest($handle, '10.0.0.1', '192.0.2.9', 443, 50000);

        self::assertSame(\CURL_PREREQFUNC_OK, $allowed);
        self::assertNull($deniedBefore);
        self::assertSame(\CURL_PREREQFUNC_ABORT, $denied);
        self::assertSame('10.0.0.1', $transfer->deniedAddress);
        self::assertSame(\CURL_PREREQFUNC_OK, self::transfer()->prerequest($handle, '10.0.0.1', '192.0.2.9', 443, 50000));
    }

    public function testToResponseWithoutStatusLineIsAProtocolError(): void
    {
        try {
            self::transfer()->toResponse([], SerializerManagerFactory::create());

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::PROTOCOL, $exception->failure);
        }
    }

    public function testToResponseWithMalformedHeadersIsAProtocolError(): void
    {
        $handle = \curl_init();
        $transfer = self::transfer();

        $transfer->header($handle, "HTTP/1.1 200 OK\r\n");
        $transfer->header($handle, " folded continuation\r\n");

        try {
            $transfer->toResponse([], SerializerManagerFactory::create());

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::PROTOCOL, $exception->failure);
        }
    }
}
