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

namespace Unit\File;

use PHPUnit\Framework\TestCase;
use Tuxxedo\File\File;

class FileTest extends TestCase
{
    public function testContentsReturnsGivenBytes(): void
    {
        $file = new File(
            name: 'greeting.txt',
            mimeType: 'text/plain',
            bytes: 'hello world',
        );

        self::assertSame(
            'hello world',
            $file->contents(),
        );
    }

    public function testSizeReflectsByteLength(): void
    {
        $file = new File(
            name: 'greeting.txt',
            mimeType: 'text/plain',
            bytes: 'hello world',
        );

        self::assertSame(
            11,
            $file->size,
        );
    }

    public function testSizeIsZeroForEmptyBytes(): void
    {
        $file = new File(
            name: null,
            mimeType: null,
            bytes: '',
        );

        self::assertSame(
            0,
            $file->size,
        );
    }

    public function testNameAndMimeTypeAreOptional(): void
    {
        $file = new File(
            name: null,
            mimeType: null,
            bytes: 'anonymous',
        );

        self::assertNull($file->name);
        self::assertNull($file->mimeType);
    }

    public function testNameAndMimeTypeAreExposedWhenGiven(): void
    {
        $file = new File(
            name: 'data.csv',
            mimeType: 'text/csv',
            bytes: 'a,b,c',
        );

        self::assertSame(
            'data.csv',
            $file->name,
        );

        self::assertSame(
            'text/csv',
            $file->mimeType,
        );
    }

    public function testBinaryBytesRoundTripUnchanged(): void
    {
        $bytes = \random_bytes(64);

        $file = new File(
            name: 'random.bin',
            mimeType: 'application/octet-stream',
            bytes: $bytes,
        );

        self::assertSame(
            $bytes,
            $file->contents(),
        );

        self::assertSame(
            64,
            $file->size,
        );
    }
}
