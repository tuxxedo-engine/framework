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
use Tuxxedo\Http\Client\Stream\StreamResource;
use Tuxxedo\Http\Stream\Stream;

class StreamResourceTest extends TestCase
{
    public function testReadsTheStreamLineByLine(): void
    {
        $resource = StreamResource::open(
            Stream::fromGenerator(
                static function (): \Generator {
                    yield "name,\"note\nwith break\"\n";
                    yield 'John,';
                    yield "x\nJane,y\n";
                },
            ),
        );

        $rows = [];

        while (($row = \fgetcsv($resource, escape: '')) !== false) {
            $rows[] = $row;
        }

        self::assertSame(
            [
                [
                    'name',
                    "note\nwith break",
                ],
                [
                    'John',
                    'x',
                ],
                [
                    'Jane',
                    'y',
                ],
            ],
            $rows,
        );

        self::assertTrue(\feof($resource));
    }

    public function testChunksLargerThanAReadKeepTheRemainder(): void
    {
        $resource = StreamResource::open(
            Stream::fromGenerator(
                static function (): \Generator {
                    yield \str_repeat('a', 10_000);
                    yield 'b';
                },
            ),
        );

        self::assertSame(\str_repeat('a', 10_000) . 'b', \stream_get_contents($resource));
    }

    public function testUnknownStreamsCannotBeOpened(): void
    {
        StreamResource::open(Stream::fromGenerator(static fn (): \Generator => yield ''));

        self::assertFalse(@\fopen('tuxxedo-client-stream://0', 'rb'));
    }
}
