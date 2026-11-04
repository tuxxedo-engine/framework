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

namespace Integration\Http\Response;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Process\ProcessCommand;
use Tuxxedo\Process\ProcessHandleInterface;
use Tuxxedo\Process\ProcessRunner;

class ResponseEmitterIntegrationTest extends TestCase
{
    private const string SERVER_SCRIPT = __DIR__ . '/../../../Fixture/Http/ResponseEmitter/server.php';
    private const float STARTUP_SECONDS = 5.0;

    private static ?ProcessHandleInterface $server = null;
    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();
        self::$server = (new ProcessRunner())->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-S',
                    '127.0.0.1:' . self::$port,
                    self::SERVER_SCRIPT,
                ],
                timeoutSeconds: null,
            ),
        );

        self::waitUntilListening();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->terminate();
        self::$server = null;
    }

    public function testRepeatedHeadersAreAllSent(): void
    {
        $response = self::request('/repeated-headers');

        self::assertSame(
            [
                '</a.css>; rel=preload',
                '</b.js>; rel=preload',
            ],
            self::values($response['headers'], 'link'),
        );

        self::assertSame(
            [
                'value',
            ],
            self::values($response['headers'], 'x-custom'),
        );
    }

    public function testFirstHeaderReplacesValueSetOutsideTheResponse(): void
    {
        $response = self::request('/replaces-existing');

        self::assertSame(
            [
                'fresh',
            ],
            self::values($response['headers'], 'x-custom'),
        );
    }

    public function testCookiesAndRawSetCookieHeadersAreAllSent(): void
    {
        $response = self::request('/cookies');
        $cookies = \array_map(
            static fn (string $value): string => \explode(';', $value)[0],
            self::values($response['headers'], 'set-cookie'),
        );

        \sort($cookies);

        self::assertSame(
            [
                'first=1',
                'last=4',
                'other=3',
                'raw=2',
            ],
            $cookies,
        );
    }

    public function testStatusLineCarriesResponseCode(): void
    {
        $response = self::request('/status');

        self::assertStringContainsString(' 404 ', $response['status']);
        self::assertSame('missing', $response['body']);
    }

    public function testContentLengthTruncatesBody(): void
    {
        $response = self::request('/content-length');

        self::assertSame('hello', $response['body']);
    }

    private static function freePort(): int
    {
        $socket = \stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            self::fail('Unable to reserve a local port');
        }

        $name = \stream_socket_get_name($socket, false);

        \fclose($socket);

        if ($name === false) {
            self::fail('Unable to read the reserved local port');
        }

        return (int) \substr($name, (int) \strrpos($name, ':') + 1);
    }

    private static function waitUntilListening(): void
    {
        $deadline = \microtime(true) + self::STARTUP_SECONDS;

        while (\microtime(true) < $deadline) {
            self::$server?->poll();

            $socket = @\stream_socket_client('tcp://127.0.0.1:' . self::$port, timeout: 0.2);

            if ($socket !== false) {
                \fclose($socket);

                return;
            }
        }

        self::fail('The PHP built-in server did not start');
    }

    /**
     * @return array{status: string, headers: list<array{0: string, 1: string}>, body: string}
     */
    private static function request(
        string $path,
    ): array {
        $socket = \stream_socket_client('tcp://127.0.0.1:' . self::$port, timeout: self::STARTUP_SECONDS);

        if ($socket === false) {
            self::fail('Unable to connect to the PHP built-in server');
        }

        \fwrite($socket, 'GET ' . $path . " HTTP/1.0\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");

        $raw = (string) \stream_get_contents($socket);

        \fclose($socket);

        self::$server?->poll();

        [$head, $body] = \array_pad(\explode("\r\n\r\n", $raw, 2), 2, '');
        $lines = \explode("\r\n", $head);
        $status = \array_shift($lines);
        $headers = [];

        foreach ($lines as $line) {
            $parts = \explode(':', $line, 2);

            if (\sizeof($parts) === 2) {
                $headers[] = [
                    \strtolower(\trim($parts[0])),
                    \trim($parts[1]),
                ];
            }
        }

        return [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * @param list<array{0: string, 1: string}> $headers
     * @return list<string>
     */
    private static function values(
        array $headers,
        string $name,
    ): array {
        $values = [];

        foreach ($headers as [$header, $value]) {
            if ($header === $name) {
                $values[] = $value;
            }
        }

        return $values;
    }
}
