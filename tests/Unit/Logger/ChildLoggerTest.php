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

namespace Unit\Logger;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Logger\ChildLogger;
use Tuxxedo\Logger\MemoryLogger;

class ChildLoggerTest extends TestCase
{
    public function testWritesToParent(): void
    {
        $parent = new MemoryLogger(
            container: new Container(),
        );
        $child = new ChildLogger(
            container: new Container(),
            parent: $parent,
            context: [
                'requestId' => 'abc',
            ],
            channel: 'payments',
        );

        $child->info('message');

        self::assertSame(1, \sizeof($parent->records));
        self::assertSame(['requestId' => 'abc'], $parent->records[0]->context);
        self::assertSame('payments', $parent->records[0]->channel);
    }

    public function testCountsOwnRecordsAndParentCountsAll(): void
    {
        $parent = new MemoryLogger(
            container: new Container(),
        );
        $child = $parent->withChannel('payments');

        $parent->info('first');
        $child->error('second');
        $child->error('third');

        self::assertSame(2, $child->total);
        self::assertSame(2, $child->totalError);
        self::assertSame(3, $parent->total);
        self::assertSame(1, $parent->totalInfo);
        self::assertSame(2, $parent->totalError);
    }

    public function testDefaultsToNoContextAndNoChannel(): void
    {
        $parent = new MemoryLogger(
            container: new Container(),
        );

        (new ChildLogger(container: new Container(), parent: $parent))->info('message');

        self::assertSame([], $parent->records[0]->context);
        self::assertNull($parent->records[0]->channel);
    }
}
