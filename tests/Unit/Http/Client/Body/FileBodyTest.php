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

namespace Unit\Http\Client\Body;

use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\File\File;
use Tuxxedo\File\LocalFile;
use Tuxxedo\Http\Client\Body\FileBody;
use Tuxxedo\Http\Stream\StreamInterface;

class FileBodyTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo-file-body-');

        if ($path === false) {
            self::fail('Failed to allocate temporary file path');
        }

        \file_put_contents($path, 'from disk');

        $this->path = $path;
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function testStreamsPathFilesFromDisk(): void
    {
        $encoded = new FileBody(new LocalFile($this->path))->encode(SerializerManagerFactory::create());

        self::assertInstanceOf(StreamInterface::class, $encoded);
        self::assertSame('from disk', $encoded->getContents());
    }

    public function testEncodesOtherFilesFromTheirContents(): void
    {
        self::assertSame(
            'in memory',
            new FileBody(new File('a.txt', 'text/plain', 'in memory'))->encode(SerializerManagerFactory::create()),
        );
    }

    public function testContentType(): void
    {
        self::assertSame('text/plain', new FileBody(new File('a.txt', 'text/plain', ''))->contentType);
        self::assertSame('application/octet-stream', new FileBody(new File('a.bin', null, ''))->contentType);
        self::assertSame('text/csv', new FileBody(new File('a.txt', 'text/plain', ''), contentType: 'text/csv')->contentType);
    }
}
