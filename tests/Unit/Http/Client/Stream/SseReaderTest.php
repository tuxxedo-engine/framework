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

namespace Unit\Http\Client\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Stream\SseReader;
use Tuxxedo\Http\Stream\SseEventInterface;
use Tuxxedo\Http\Stream\Stream;

class SseReaderTest extends TestCase
{
    public function testParsesEvents(): void
    {
        $events = \iterator_to_array(
            SseReader::read(
                Stream::fromGenerator(
                    static function (): \Generator {
                        yield ": comment\n";
                        yield "event: update\nid: 7\nretry: 1500\ndata: first\ndata:second\n\n";
                        yield "retry: 0\nid: a\0b\nunknown: x\ndata\n\n";
                        yield "event: lonely\n\n";
                        yield "data: unterminated\n";
                    },
                ),
            ),
            false,
        );

        self::assertSame(
            [
                [
                    "first\nsecond",
                    '7',
                    'update',
                    1500,
                ],
                [
                    '',
                    null,
                    null,
                    null,
                ],
            ],
            \array_map(
                static fn (SseEventInterface $event): array => [
                    $event->data,
                    $event->id,
                    $event->event,
                    $event->retry,
                ],
                $events,
            ),
        );
    }
}
