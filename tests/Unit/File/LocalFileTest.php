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
use Tuxxedo\File\FileException;
use Tuxxedo\File\LocalFile;

class LocalFileTest extends TestCase
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
    ): string {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo-local-file-');

        if ($path === false) {
            self::fail('Failed to allocate temporary file path');
        }

        \file_put_contents($path, $bytes);

        $this->temporaryPaths[] = $path;

        return $path;
    }

    public function testContentsReturnsFileBytes(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'stored bytes',
        );

        $file = new LocalFile(
            path: $path,
        );

        self::assertSame(
            'stored bytes',
            $file->contents(),
        );
    }

    public function testSizeReflectsFileByteLength(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'twelve bytes',
        );

        $file = new LocalFile(
            path: $path,
        );

        self::assertSame(
            12,
            $file->size,
        );
    }

    public function testNameDefaultsToBasenameWhenNotGiven(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'ignored',
        );

        $file = new LocalFile(
            path: $path,
        );

        self::assertNull($file->name);
    }

    public function testExplicitNameIsPreservedOverPathBasename(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'ignored',
        );

        $file = new LocalFile(
            path: $path,
            name: 'report.txt',
        );

        self::assertSame(
            'report.txt',
            $file->name,
        );
    }

    public function testMimeTypeReflectsConstructorArgument(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'ignored',
        );

        $file = new LocalFile(
            path: $path,
            mimeType: 'application/pdf',
        );

        self::assertSame(
            'application/pdf',
            $file->mimeType,
        );
    }

    public function testConstructorRejectsNonExistentPath(): void
    {
        $this->expectException(FileException::class);

        new LocalFile(
            path: '/nonexistent/path/' . \bin2hex(\random_bytes(8)),
        );
    }

    public function testConstructorRejectsDirectoryPath(): void
    {
        $this->expectException(FileException::class);

        new LocalFile(
            path: \sys_get_temp_dir(),
        );
    }

    public function testContentsThrowsWhenFileVanishesAfterConstruction(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'transient',
        );

        $file = new LocalFile(
            path: $path,
        );

        @\unlink($path);

        $this->expectException(FileException::class);

        (void) $file->contents();
    }

    public function testContentsReReadsBetweenCalls(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'first',
        );

        $file = new LocalFile(
            path: $path,
        );

        self::assertSame(
            'first',
            $file->contents(),
        );

        \file_put_contents($path, 'second');

        self::assertSame(
            'second',
            $file->contents(),
        );
    }

    public function testSizeReReadsBetweenCalls(): void
    {
        $path = $this->writeTemporaryFile(
            bytes: 'abc',
        );

        $file = new LocalFile(
            path: $path,
        );

        self::assertSame(
            3,
            $file->size,
        );

        \file_put_contents($path, 'abcdef');

        self::assertSame(
            6,
            $file->size,
        );
    }
}
