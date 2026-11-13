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

namespace Tuxxedo\Http\Client\Stream;

use Tuxxedo\Http\Stream\StreamException;
use Tuxxedo\Http\Stream\StreamInterface;

class StreamResource
{
    private const string PROTOCOL = 'tuxxedo-client-stream';

    /**
     * @var array<int, StreamInterface>
     */
    private static array $pending = [];

    /**
     * @var resource|null
     */
    public mixed $context = null;

    private ?StreamInterface $stream = null;
    private string $buffer = '';

    /**
     * @return resource
     *
     * @throws StreamException
     */
    public static function open(
        StreamInterface $stream,
    ): mixed {
        if (!\in_array(self::PROTOCOL, \stream_get_wrappers(), true)) {
            \stream_wrapper_register(self::PROTOCOL, self::class);
        }

        $id = \spl_object_id($stream);
        $path = self::PROTOCOL . '://' . $id;

        self::$pending[$id] = $stream;

        try {
            $resource = \fopen($path, 'rb');
        } finally {
            unset(self::$pending[$id]);
        }

        if ($resource === false) {
            throw StreamException::fromUnreadableFile($path); // @codeCoverageIgnore
        }

        return $resource;
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath,
    ): bool {
        $this->stream = self::$pending[(int) \substr($path, \strlen(self::PROTOCOL) + 3)] ?? null;

        return $this->stream !== null;
    }

    public function stream_read(
        int $count,
    ): string {
        if ($this->buffer === '') {
            $this->buffer = $this->stream?->read() ?? '';
        }

        $data = \substr($this->buffer, 0, $count);
        $this->buffer = \substr($this->buffer, \strlen($data));

        return $data;
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }

    public function stream_eof(): bool
    {
        return $this->buffer === '' && ($this->stream?->eof() ?? true);
    }
}
