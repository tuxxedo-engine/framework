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

namespace Tuxxedo\Console\Stream;

use Tuxxedo\Console\ConsoleException;

class PhpInputStream implements InputStreamInterface
{
    public bool $isTerminal {
        get {
            if (!\is_resource($this->resource)) {
                return false;
            }

            return \stream_isatty($this->resource);
        }
    }

    /**
     * @param resource $resource
     */
    public function __construct(
        private mixed $resource,
    ) {
    }

    /**
     * @param positive-int $length
     */
    public function read(
        int $length,
    ): string {
        $resource = $this->requireResource();
        $bytes = \fread($resource, $length);

        if ($bytes === false) {
            throw ConsoleException::fromStreamReadFailure(); // @codeCoverageIgnore
        }

        return $bytes;
    }

    public function readLine(): ?string
    {
        $resource = $this->requireResource();
        $line = \fgets($resource);

        if ($line === false) {
            if (\feof($resource)) {
                return null;
            }

            throw ConsoleException::fromStreamReadFailure(); // @codeCoverageIgnore
        }

        return \rtrim($line, "\r\n");
    }

    public function readAll(): string
    {
        $resource = $this->requireResource();
        $contents = \stream_get_contents($resource);

        if ($contents === false) {
            throw ConsoleException::fromStreamReadFailure(); // @codeCoverageIgnore
        }

        return $contents;
    }

    /**
     * @codeCoverageIgnore
     */
    public function hiddenKeys(): \Generator
    {
        $resource = $this->requireResource();

        if (\PHP_OS_FAMILY === 'Windows') {
            yield from self::windowsKeys($resource);

            return;
        }

        yield from self::posixKeys($resource);
    }

    public function close(): void
    {
        if (!\is_resource($this->resource)) {
            return;
        }

        @\fclose($this->resource);
    }

    /**
     * @param resource $terminal
     * @return \Generator<int, string>
     *
     * @throws ConsoleException
     *
     * @codeCoverageIgnore
     */
    private static function posixKeys(
        mixed $terminal,
    ): \Generator {
        $saved = self::stty(
            terminal: $terminal,
            arguments: [
                '-g',
            ],
        );

        if ($saved === null) {
            throw ConsoleException::fromHiddenInputUnavailable();
        }

        $raw = self::stty(
            terminal: $terminal,
            arguments: [
                '-echo',
                '-icanon',
                'min',
                '1',
                'time',
                '0',
            ],
        );

        if ($raw === null) {
            throw ConsoleException::fromHiddenInputUnavailable();
        }

        try {
            while (($key = self::readCharacter($terminal)) !== null) {
                yield $key;
            }
        } finally {
            self::stty(
                terminal: $terminal,
                arguments: [
                    \trim($saved),
                ],
            );
        }
    }

    /**
     * @param resource $terminal
     * @return \Generator<int, string>
     *
     * @throws ConsoleException
     *
     * @codeCoverageIgnore
     */
    private static function windowsKeys(
        mixed $terminal,
    ): \Generator {
        $pipes = [];
        $process = @\proc_open(
            [
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                \join(
                    '; ',
                    [
                        '$encoding = New-Object System.Text.UTF8Encoding $false',
                        '[Console]::OutputEncoding = $encoding',
                        '$out = [Console]::Out',
                        'while ($true) { $key = [Console]::ReadKey($true); if ($key.Key -eq \'Enter\') { $out.Write([char] 10); $out.Flush(); break }; if ($key.Key -eq \'Backspace\') { $out.Write([char] 8); $out.Flush(); continue }; if ([int] $key.KeyChar -ge 32) { $out.Write($key.KeyChar); $out.Flush() } }',
                    ],
                ),
            ],
            [
                0 => $terminal,
                1 => [
                    'pipe',
                    'w',
                ],
                2 => [
                    'pipe',
                    'w',
                ],
            ],
            $pipes,
        );

        if ($process === false) {
            throw ConsoleException::fromHiddenInputUnavailable();
        }

        try {
            while (($key = self::readCharacter($pipes[1])) !== null) {
                yield $key;

                if ($key === "\n") {
                    return;
                }
            }
        } finally {
            \fclose($pipes[1]);
            \fclose($pipes[2]);
            \proc_close($process);
        }

        throw ConsoleException::fromHiddenInputUnavailable();
    }

    /**
     * @param resource $terminal
     * @param list<string> $arguments
     *
     * @codeCoverageIgnore
     */
    private static function stty(
        mixed $terminal,
        array $arguments,
    ): ?string {
        $pipes = [];
        $process = @\proc_open(
            [
                'stty',
                ...$arguments,
            ],
            [
                0 => $terminal,
                1 => [
                    'pipe',
                    'w',
                ],
                2 => [
                    'pipe',
                    'w',
                ],
            ],
            $pipes,
        );

        if ($process === false) {
            return null;
        }

        $output = \stream_get_contents($pipes[1]);

        \stream_get_contents($pipes[2]);

        \fclose($pipes[1]);
        \fclose($pipes[2]);

        if (\proc_close($process) !== 0 || $output === false) {
            return null;
        }

        return $output;
    }

    /**
     * @param resource $stream
     *
     * @codeCoverageIgnore
     */
    private static function readCharacter(
        mixed $stream,
    ): ?string {
        $character = \fread($stream, 1);

        if ($character === false || $character === '') {
            return null;
        }

        $byte = \ord($character);
        $length = match (true) {
            $byte >= 0xF0 => 4,
            $byte >= 0xE0 => 3,
            $byte >= 0xC0 => 2,
            default => 1,
        };

        while (\strlen($character) < $length) {
            $next = \fread($stream, 1);

            if ($next === false || $next === '') {
                break;
            }

            $character .= $next;
        }

        return $character;
    }

    /**
     * @return resource
     *
     * @throws ConsoleException
     */
    private function requireResource(): mixed
    {
        if (!\is_resource($this->resource)) {
            throw ConsoleException::fromStreamNotOpen();
        }

        return $this->resource;
    }
}
