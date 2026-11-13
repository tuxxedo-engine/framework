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

namespace Support\Http\Client;

use PHPUnit\Framework\Assert;
use Tuxxedo\Process\ProcessCommand;
use Tuxxedo\Process\ProcessHandleInterface;
use Tuxxedo\Process\ProcessRunner;

class LocalHttpServer
{
    private const string SERVER_SCRIPT = __DIR__ . '/../../../Fixture/Http/Client/server.php';
    private const float STARTUP_SECONDS = 5.0;

    private function __construct(
        private readonly ProcessHandleInterface $process,
        public readonly int $port,
    ) {
    }

    public static function start(): self
    {
        $port = self::freePort();
        $server = new self(
            process: new ProcessRunner()->start(
                new ProcessCommand(
                    binary: \PHP_BINARY,
                    arguments: [
                        '-S',
                        '127.0.0.1:' . $port,
                        self::SERVER_SCRIPT,
                    ],
                    timeoutSeconds: null,
                ),
            ),
            port: $port,
        );

        $server->waitUntilListening();

        return $server;
    }

    public static function freePort(): int
    {
        $socket = \stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            Assert::fail('Unable to reserve a local port');
        }

        $name = \stream_socket_get_name($socket, false);

        \fclose($socket);

        if ($name === false) {
            Assert::fail('Unable to read the reserved local port');
        }

        return (int) \substr($name, (int) \strrpos($name, ':') + 1);
    }

    public function url(
        string $path,
    ): string {
        return 'http://127.0.0.1:' . $this->port . $path;
    }

    public function stop(): void
    {
        $this->process->terminate();
    }

    private function waitUntilListening(): void
    {
        $deadline = \microtime(true) + self::STARTUP_SECONDS;

        while (\microtime(true) < $deadline) {
            $this->process->poll();

            $socket = @\stream_socket_client('tcp://127.0.0.1:' . $this->port, timeout: 0.2);

            if ($socket !== false) {
                \fclose($socket);

                return;
            }
        }

        Assert::fail('The PHP built-in server did not start');
    }
}
