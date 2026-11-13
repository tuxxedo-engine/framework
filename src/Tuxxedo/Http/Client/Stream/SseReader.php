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

use Tuxxedo\Http\Stream\SseEvent;
use Tuxxedo\Http\Stream\SseEventInterface;
use Tuxxedo\Http\Stream\StreamException;
use Tuxxedo\Http\Stream\StreamInterface;

class SseReader
{
    /**
     * @return \Generator<int, SseEventInterface>
     *
     * @throws StreamException
     */
    public static function read(
        StreamInterface $stream,
    ): \Generator {
        $data = [];
        $id = null;
        $event = null;
        $retry = null;

        foreach (LineReader::read($stream) as $line) {
            if ($line === '') {
                if (\sizeof($data) > 0) {
                    yield SseEvent::create(
                        data: \join("\n", $data),
                        id: $id,
                        event: $event,
                        retry: $retry,
                    );
                }

                $data = [];
                $id = null;
                $event = null;
                $retry = null;

                continue;
            }

            if (\str_starts_with($line, ':')) {
                continue;
            }

            [$field, $value] = \str_contains($line, ':')
                ? \explode(':', $line, 2)
                : [
                    $line,
                    '',
                ];

            if (\str_starts_with($value, ' ')) {
                $value = \substr($value, 1);
            }

            if ($field === 'data') {
                $data[] = $value;
            } elseif ($field === 'event') {
                $event = $value;
            } elseif ($field === 'id' && !\str_contains($value, "\0")) {
                $id = $value;
            } elseif ($field === 'retry' && \ctype_digit($value) && (int) $value > 0) {
                $retry = (int) $value;
            }
        }
    }
}
