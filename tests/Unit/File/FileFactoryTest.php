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
use Tuxxedo\File\FileException;
use Tuxxedo\File\FileFactory;
use Tuxxedo\File\LocalFile;

class FileFactoryTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryPaths as $path) {
            if (\is_file($path)) {
                @\unlink($path);
            }
        }

        $this->temporaryPaths = [];
    }

    private function writeTemporaryFile(
        string $bytes,
        string $suffix = '',
    ): string {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo-file-factory-') . $suffix;

        \file_put_contents($path, $bytes);

        $this->temporaryPaths[] = $path;

        return $path;
    }

    public function testFromBytesProducesFileInstance(): void
    {
        $file = FileFactory::fromBytes(
            bytes: 'hello',
        );

        self::assertInstanceOf(
            File::class,
            $file,
        );

        self::assertSame(
            'hello',
            $file->contents(),
        );

        self::assertSame(
            5,
            $file->size,
        );
    }

    public function testFromBytesPassesNameAndMimeTypeThrough(): void
    {
        $file = FileFactory::fromBytes(
            bytes: 'a,b,c',
            name: 'data.csv',
            mimeType: 'text/csv',
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

    public function testFromBytesLeavesMetadataNullWhenNotGiven(): void
    {
        $file = FileFactory::fromBytes(
            bytes: 'anonymous',
        );

        self::assertNull($file->name);
        self::assertNull($file->mimeType);
    }

    public function testFromPathProducesLocalFileInstance(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'file contents',
        );

        $file = FileFactory::fromPath(
            path: $path,
        );

        self::assertInstanceOf(
            LocalFile::class,
            $file,
        );

        self::assertSame(
            'file contents',
            $file->contents(),
        );
    }

    public function testFromPathDefaultsNameToBasename(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'body',
        );

        $file = FileFactory::fromPath(
            path: $path,
        );

        self::assertSame(
            \basename($path),
            $file->name,
        );
    }

    public function testFromPathExplicitNameOverridesBasename(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'body',
        );

        $file = FileFactory::fromPath(
            path: $path,
            name: 'custom.txt',
        );

        self::assertSame(
            'custom.txt',
            $file->name,
        );
    }

    public function testFromPathExplicitMimeTypeIsPreserved(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'body',
        );

        $file = FileFactory::fromPath(
            path: $path,
            mimeType: 'application/octet-stream',
        );

        self::assertSame(
            'application/octet-stream',
            $file->mimeType,
        );
    }

    public function testFromPathSniffsMimeTypeWhenAbsent(): void
    {
        if (!\extension_loaded('fileinfo')) {
            self::markTestSkipped('fileinfo extension not loaded');
        }

        $path = $this->writeTemporaryFile(
            bytes: 'hello, this is plain text.',
        );

        $file = FileFactory::fromPath(
            path: $path,
        );

        self::assertNotNull($file->mimeType);
        self::assertStringStartsWith(
            'text/',
            $file->mimeType,
        );
    }

    public function testFromPathRejectsNonExistentPath(): void
    {
        $this->expectException(FileException::class);

        (void) FileFactory::fromPath(
            path: '/nonexistent/' . \bin2hex(\random_bytes(8)),
        );
    }
}
