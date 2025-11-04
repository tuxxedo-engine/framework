<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Collection;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Collection\FileCollection;

class FileCollectionTest extends TestCase
{
    private const string GOOD_DIRECTORY = __DIR__ . '/../../Fixtures/Collection/Files';
    private const string BAD_DIRECTORY = __DIR__ . '/../../Fixtures/Collection/Files/Bad';

    public function testFromGlob(): void
    {
        $collection = FileCollection::fromGlob(self::GOOD_DIRECTORY . '/*.txt');

        $this->assertSame($collection->count(), 3);
    }

    public function testFromGlobError(): void
    {
        $collection = FileCollection::fromGlob(self::BAD_DIRECTORY . '/*.txt');

        $this->assertSame($collection->count(), 0);
    }

    public function testFromDirectory(): void
    {
        $collection = FileCollection::fromDirectory(self::GOOD_DIRECTORY);

        $this->assertSame($collection->count(), 4);
    }

    public function testFromDirectoryError(): void
    {
        $collection = FileCollection::fromDirectory(self::BAD_DIRECTORY);

        $this->assertSame($collection->count(), 0);
    }

    public function testFromRecursiveDirectory(): void
    {
        $collection = FileCollection::fromRecursiveDirectory(self::GOOD_DIRECTORY);

        $this->assertSame($collection->count(), 1);
    }

    public function testFromRecursiveDirectoryError(): void
    {
        $collection = FileCollection::fromRecursiveDirectory(self::BAD_DIRECTORY);

        $this->assertSame($collection->count(), 0);
    }

    public function testFromFileType(): void
    {
        $collection = FileCollection::fromFileType(
            directory: self::GOOD_DIRECTORY,
            extension: '.txt',
        );

        $this->assertSame($collection->count(), 3);
    }

    public function testFromFileTypeEmpty(): void
    {
        $collection = FileCollection::fromFileType(
            directory: self::GOOD_DIRECTORY,
            extension: '.php',
        );

        $this->assertSame($collection->count(), 0);
    }

    public function testFromFileTypeError(): void
    {
        $collection = FileCollection::fromFileType(
            directory: self::BAD_DIRECTORY,
            extension: '.txt',
        );

        $this->assertSame($collection->count(), 0);
    }

    public function testFromRecursiveFileType(): void
    {
        $collection = FileCollection::fromRecursiveFileType(
            directory: self::GOOD_DIRECTORY,
            extension: '.txt',
        );

        $this->assertSame($collection->count(), 4);
    }

    public function testFromRecursiveFileTypeEmpty(): void
    {
        $collection = FileCollection::fromRecursiveFileType(
            directory: self::GOOD_DIRECTORY,
            extension: '.php',
        );

        $this->assertSame($collection->count(), 0);
    }

    public function testFromRecursiveFileTypeError(): void
    {
        $collection = FileCollection::fromRecursiveFileType(
            directory: self::BAD_DIRECTORY,
            extension: '.txt',
        );

        $this->assertSame($collection->count(), 0);
    }
}
