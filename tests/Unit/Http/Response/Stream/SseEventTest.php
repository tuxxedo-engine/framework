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

namespace Unit\Http\Response\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Response\Stream\SseEvent;
use Tuxxedo\Http\Response\Stream\SseEventInterface;

class SseEventTest extends TestCase
{
    public function testCreateProducesSseEventInterfaceInstance(): void
    {
        $event = SseEvent::create(
            data: 'payload',
        );

        self::assertInstanceOf(SseEventInterface::class, $event);
    }

    public function testCreateSetsDataOnly(): void
    {
        $event = SseEvent::create(
            data: 'payload',
        );

        self::assertSame('payload', $event->data);
        self::assertNull($event->id);
        self::assertNull($event->event);
        self::assertNull($event->retry);
        self::assertNull($event->comment);
    }

    public function testCreateSetsAllOptionalFields(): void
    {
        $event = SseEvent::create(
            data: 'payload',
            id: 'evt-1',
            event: 'message',
            retry: 5000,
        );

        self::assertSame('payload', $event->data);
        self::assertSame('evt-1', $event->id);
        self::assertSame('message', $event->event);
        self::assertSame(5000, $event->retry);
        self::assertNull($event->comment);
    }

    public function testJsonEncodesScalarData(): void
    {
        $event = SseEvent::json(
            data: 'hello',
        );

        self::assertSame('"hello"', $event->data);
    }

    public function testJsonEncodesArrayData(): void
    {
        $event = SseEvent::json(
            data: [
                'foo' => 'bar',
                'count' => 3,
            ],
        );

        self::assertSame('{"foo":"bar","count":3}', $event->data);
    }

    public function testJsonPropagatesOptionalFields(): void
    {
        $event = SseEvent::json(
            data: [
                'k' => 'v',
            ],
            id: 'evt-2',
            event: 'update',
            retry: 1000,
        );

        self::assertSame('{"k":"v"}', $event->data);
        self::assertSame('evt-2', $event->id);
        self::assertSame('update', $event->event);
        self::assertSame(1000, $event->retry);
    }

    public function testJsonThrowsOnUnencodableData(): void
    {
        self::expectException(\JsonException::class);

        SseEvent::json(
            data: NAN,
        );
    }

    public function testCommentSetsCommentOnlyAndLeavesDataNull(): void
    {
        $event = SseEvent::comment(
            comment: 'debug-marker',
        );

        self::assertSame('debug-marker', $event->comment);
        self::assertNull($event->data);
        self::assertNull($event->id);
        self::assertNull($event->event);
        self::assertNull($event->retry);
    }

    public function testKeepaliveProducesKeepaliveComment(): void
    {
        $event = SseEvent::keepalive();

        self::assertSame('keepalive', $event->comment);
        self::assertNull($event->data);
    }
}
