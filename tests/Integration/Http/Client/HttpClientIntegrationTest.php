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

namespace Integration\Http\Client;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\LocalHttpServer;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\ClientProgressInterface;
use Tuxxedo\Http\Client\HttpClient;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpRules;
use Tuxxedo\Http\Client\MemoryCookieJar;
use Tuxxedo\Http\Client\RetryPolicy;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\Duration;

#[RequiresPhpExtension('curl')]
class HttpClientIntegrationTest extends TestCase
{
    private static ?LocalHttpServer $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server = LocalHttpServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    private static function client(): HttpClient
    {
        self::assertNotNull(self::$server);

        return new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            baseUrl: self::$server->url(''),
        );
    }

    public function testRedirectsAndCookies(): void
    {
        $client = self::client()->withCookieJar(new MemoryCookieJar());

        $client->get('/cookie');

        $response = $client->get('/redirect');

        self::assertSame(200, $response->statusCode);
        self::assertTrue($response->info->redirected);
        self::assertStringContainsString('"redirected":"1"', $response->body->getRaw());
        self::assertStringContainsString('"cookie":"session=abc"', $response->body->getRaw());
    }

    public function testDownload(): void
    {
        $path = \sys_get_temp_dir() . '/tuxxedo-integration-' . \bin2hex(\random_bytes(4));

        try {
            $response = self::client()->download('/big?size=100000', $path);

            self::assertSame(100_000, \filesize($path));
            self::assertSame(100_000, \strlen($response->body->getRaw()));
        } finally {
            @\unlink($path);
        }
    }

    public function testStreamingBodies(): void
    {
        $client = self::client();
        $chunks = [];
        $stream = $client->stream(Method::GET, '/chunks')->body->getStream();

        while (($chunk = $stream->read()) !== null) {
            $chunks[] = $chunk;
        }

        $events = [];

        foreach ($client->streamSse(Method::GET, '/sse') as $event) {
            $events[] = $event->event . ':' . $event->data;
        }

        $names = [];

        foreach ($client->streamCsv(Method::GET, '/csv', BodyContextFixture::class) as $record) {
            $names[] = $record->name . ':' . $record->age;
        }

        self::assertSame('onetwothree', \join('', $chunks));
        self::assertSame(
            [
                'greeting:hello',
                ':world',
            ],
            $events,
        );

        self::assertSame(
            [
                'John:30',
                'Jane:40',
            ],
            $names,
        );
    }

    public function testPrivateNetworksAreDeniedAfterResolution(): void
    {
        self::assertNotNull(self::$server);

        try {
            self::client()
                ->withRules(new HttpRules()->denyPrivateNetworks())
                ->get('http://localhost:' . self::$server->port . '/echo');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RULE, $exception->failure);
        }
    }

    public function testDeferredRequestsRunThroughOneBatch(): void
    {
        $client = self::client();
        $deferred = $client->deferred();
        $created = $deferred->get('/status/201');
        $accepted = $deferred->get('/status/202');
        $echo = $deferred->post(
            url: '/echo',
            body: new JsonBody(
                [
                    'a' => 1,
                ],
            ),
        );

        $client->wait($created, $accepted, $echo);

        self::assertSame(201, $created->result()->statusCode);
        self::assertSame(202, $accepted->result()->statusCode);
        self::assertStringContainsString('"method":"POST"', $echo->result()->body->getRaw());
    }

    public function testRetryTimersRunInsideABatch(): void
    {
        $client = self::client()->withRetry(
            new RetryPolicy(
                times: 1,
                delay: Duration::fromMilliseconds(50),
                maxDelay: Duration::fromMilliseconds(50),
            ),
        );

        $flaky = $client->deferred()->get('/status/503');
        $slow = $client->deferred()->get('/slow?ms=300');

        $client->wait($flaky, $slow);

        self::assertSame(503, $flaky->result()->statusCode);
        self::assertSame('slow', $slow->result()->body->getRaw());
    }

    public function testPendingsFromTwoClientsShareAWait(): void
    {
        $first = self::client()->deferred()->get('/slow?ms=200');
        $second = self::client()->deferred()->get('/status/204');

        self::client()->wait($first, $second);

        self::assertSame('slow', $first->result()->body->getRaw());
        self::assertSame(204, $second->result()->statusCode);
    }

    public function testDownloadProgressAndAbort(): void
    {
        $path = \sys_get_temp_dir() . '/tuxxedo-integration-' . \bin2hex(\random_bytes(4));
        $reports = [];

        try {
            self::client()->download(
                url: '/big?size=200000',
                path: $path,
                progress: static function (ClientProgressInterface $progress) use (&$reports): void {
                    $reports[] = [
                        $progress->downloaded,
                        $progress->downloadTotal,
                    ];
                },
            );

            $last = \end($reports);

            self::assertNotFalse($last);
            self::assertSame(200_000, $last[0]);
            self::assertContains(
                $last[1],
                [
                    200_000,
                    null,
                ],
            );

            \unlink($path);

            try {
                self::client()
                    ->withProgress(static fn (ClientProgressInterface $progress): bool => $progress->downloaded === 0)
                    ->download('/big?size=200000', $path);

                self::fail('Expected HttpClientException was not thrown');
            } catch (HttpClientException $exception) {
                self::assertSame(HttpClientFailure::ABORTED, $exception->failure);
            }

            self::assertFileDoesNotExist($path);
        } finally {
            @\unlink($path);
        }
    }

    public function testDeferredDownloadsRunConcurrently(): void
    {
        $directory = \sys_get_temp_dir();
        $first = $directory . '/tuxxedo-integration-' . \bin2hex(\random_bytes(4));
        $second = $directory . '/tuxxedo-integration-' . \bin2hex(\random_bytes(4));
        $client = self::client();
        $deferred = $client->deferred();

        try {
            $one = $deferred->download('/big?size=150000', $first);
            $two = $deferred->download('/redirect', $second);
            $capped = $deferred->download('/big?size=150000', $first . '.capped', maxSize: 1000);
            $unwritable = $deferred->download('/big?size=10', $directory . '/missing-' . \bin2hex(\random_bytes(4)) . '/file');

            $client->wait($one, $two, $capped, $unwritable);

            self::assertInstanceOf(HttpClientException::class, $unwritable->exception);
            self::assertSame(HttpClientFailure::REQUEST, $unwritable->exception->failure);

            self::assertSame(150_000, \filesize($first));
            self::assertSame(200, $one->result()->statusCode);
            self::assertStringContainsString('"redirected":"1"', (string) \file_get_contents($second));
            self::assertFalse($capped->succeeded);
            self::assertFileDoesNotExist($first . '.capped');
            self::assertSame([], \glob($directory . '/tuxxedo-integration-*.part'));
        } finally {
            @\unlink($first);
            @\unlink($second);
        }
    }
}
