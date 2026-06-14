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

namespace Unit\Http\Request\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\EnvironmentUploadedFilesContext;
use Tuxxedo\Http\Request\Context\UploadedFileException;
use Tuxxedo\Http\UploadedFile;

class EnvironmentUploadedFilesContextTest extends TestCase
{
    protected function tearDown(): void
    {
        $_FILES = [];
    }

    public function testHasReturnsTrueWhenFileExists(): void
    {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'error' => \UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'full_path' => 'photo.jpg',
        ];

        self::assertTrue((new EnvironmentUploadedFilesContext())->has('avatar'));
    }

    public function testHasReturnsFalseWhenFileDoesNotExist(): void
    {
        self::assertFalse((new EnvironmentUploadedFilesContext())->has('avatar'));
    }

    public function testHasWithDotNotationPath(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/test',
                'full_path' => 'photo.jpg',
            ],
        ];

        self::assertTrue((new EnvironmentUploadedFilesContext())->has('gallery.0'));
    }

    public function testHasReturnsFalseWhenDotNotationPathDoesNotResolveToFile(): void
    {
        $_FILES['gallery'] = [
            [
                'not-a-file-field' => 'value',
            ],
        ];

        self::assertFalse((new EnvironmentUploadedFilesContext())->has('gallery.0'));
    }

    public function testFileThrowsForMissingFile(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentUploadedFilesContext())->file('avatar');
    }

    /**
     * @return \Generator<array{0: int, 1: class-string<\Throwable>}>
     */
    public static function uploadErrorDataProvider(): \Generator
    {
        yield [
            \UPLOAD_ERR_INI_SIZE,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_FORM_SIZE,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_PARTIAL,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_NO_FILE,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_NO_TMP_DIR,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_CANT_WRITE,
            UploadedFileException::class,
        ];

        yield [
            \UPLOAD_ERR_EXTENSION,
            UploadedFileException::class,
        ];
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('uploadErrorDataProvider')]
    public function testFileThrowsForUploadError(
        int $errorCode,
        string $exception,
    ): void {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 0,
            'error' => $errorCode,
            'tmp_name' => '',
            'full_path' => '',
        ];

        $this->expectException($exception);

        (new EnvironmentUploadedFilesContext())->file('avatar');
    }

    public function testFileReturnsUploadedFile(): void
    {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'error' => \UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'full_path' => 'photo.jpg',
        ];

        $file = (new EnvironmentUploadedFilesContext())->file('avatar');

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertSame('photo.jpg', $file->name);
        self::assertSame(1024, $file->size);
        self::assertSame('/tmp/test', $file->temporaryPath);
        self::assertSame('photo.jpg', $file->browserPath);
    }

    public function testFileWithDotNotationPath(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/test',
                'full_path' => 'photo.jpg',
            ],
        ];

        $file = (new EnvironmentUploadedFilesContext())->file('gallery.0');

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertSame('photo.jpg', $file->name);
    }

    public function testArrayOfFileReturnsAllFiles(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/a',
                'full_path' => 'a.jpg',
            ],
            [
                'name' => 'b.jpg',
                'type' => 'image/jpeg',
                'size' => 2048,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/b',
                'full_path' => 'b.jpg',
            ],
        ];

        $files = (new EnvironmentUploadedFilesContext())->arrayOfFile('gallery');

        self::assertCount(2, $files);
        self::assertSame('a.jpg', $files[0]->name);
        self::assertSame('b.jpg', $files[1]->name);
    }

    public function testArrayOfFileUnzipsPhpParallelArrayShape(): void
    {
        $_FILES['images'] = [
            'name' => [
                'a.jpg',
                'b.jpg',
            ],
            'type' => [
                'image/jpeg',
                'image/jpeg',
            ],
            'size' => [
                1024,
                2048,
            ],
            'error' => [
                \UPLOAD_ERR_OK,
                \UPLOAD_ERR_OK,
            ],
            'tmp_name' => [
                '/tmp/a',
                '/tmp/b',
            ],
            'full_path' => [
                'a.jpg',
                'b.jpg',
            ],
        ];

        $files = (new EnvironmentUploadedFilesContext())->arrayOfFile('images');

        self::assertCount(2, $files);
        self::assertSame('a.jpg', $files[0]->name);
        self::assertSame('b.jpg', $files[1]->name);
    }

    public function testArrayOfFileThrowsForMissingPath(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentUploadedFilesContext())->arrayOfFile('gallery');
    }

    public function testArrayOfFileThrowsWhenPathResolvesToSingleFile(): void
    {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'error' => \UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'full_path' => 'photo.jpg',
        ];

        $this->expectException(UploadedFileException::class);

        (new EnvironmentUploadedFilesContext())->arrayOfFile('avatar');
    }

    public function testArrayOfFileThrowsForAssociativeKeyedMap(): void
    {
        $_FILES['user'] = [
            'name' => [
                'avatar' => 'a.jpg',
            ],
            'type' => [
                'avatar' => 'image/jpeg',
            ],
            'size' => [
                'avatar' => 1024,
            ],
            'error' => [
                'avatar' => \UPLOAD_ERR_OK,
            ],
            'tmp_name' => [
                'avatar' => '/tmp/a',
            ],
            'full_path' => [
                'avatar' => 'a.jpg',
            ],
        ];

        $this->expectException(UploadedFileException::class);

        (new EnvironmentUploadedFilesContext())->arrayOfFile('user');
    }

    public function testArrayOfFileThrowsOnFirstUploadError(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/a',
                'full_path' => 'a.jpg',
            ],
            [
                'name' => 'b.jpg',
                'type' => 'image/jpeg',
                'size' => 0,
                'error' => \UPLOAD_ERR_PARTIAL,
                'tmp_name' => '',
                'full_path' => 'b.jpg',
            ],
        ];

        $this->expectException(UploadedFileException::class);

        (new EnvironmentUploadedFilesContext())->arrayOfFile('gallery');
    }

    public function testHasReturnsTrueForListOfLeaves(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/a',
                'full_path' => 'a.jpg',
            ],
            [
                'name' => 'b.jpg',
                'type' => 'image/jpeg',
                'size' => 2048,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/b',
                'full_path' => 'b.jpg',
            ],
        ];

        self::assertTrue((new EnvironmentUploadedFilesContext())->has('gallery'));
    }

    public function testHasReturnsFalseWhenListEntriesAreNotLeaves(): void
    {
        $_FILES['docs'] = [
            [
                'inner' => [
                    'not-a-file-field' => 'value',
                ],
            ],
        ];

        self::assertFalse((new EnvironmentUploadedFilesContext())->has('docs'));
    }

    public function testFileThrowsWhenPathResolvesToArray(): void
    {
        $_FILES['gallery'] = [
            [
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'error' => \UPLOAD_ERR_OK,
                'tmp_name' => '/tmp/a',
                'full_path' => 'a.jpg',
            ],
        ];

        $this->expectException(UploadedFileException::class);

        (new EnvironmentUploadedFilesContext())->file('gallery');
    }

    public function testArrayOfFileThrowsWhenListEntriesAreNotLeaves(): void
    {
        $_FILES['docs'] = [
            [
                'inner' => [
                    'not-a-file-field' => 'value',
                ],
            ],
        ];

        $this->expectException(UploadedFileException::class);

        (new EnvironmentUploadedFilesContext())->arrayOfFile('docs');
    }

    public function testNormalizedTreeIsCachedAcrossCalls(): void
    {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'error' => \UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'full_path' => 'photo.jpg',
        ];

        $context = new EnvironmentUploadedFilesContext();

        self::assertTrue($context->has('avatar'));

        $_FILES = [];

        self::assertTrue($context->has('avatar'));
    }

    public function testHasReturnsFalseForMalformedParallelArrayStructure(): void
    {
        $_FILES['x'] = [
            'name' => [
                'inner' => [
                    'a.jpg',
                ],
            ],
            'error' => [
                'inner' => 0,
            ],
        ];

        self::assertFalse((new EnvironmentUploadedFilesContext())->has('x'));
    }

    public function testArrayOfFileResolvesDeeplyNestedPhpInputShape(): void
    {
        $_FILES['media'] = [
            'name' => [
                'photos' => [
                    'a.jpg',
                    'b.jpg',
                ],
            ],
            'type' => [
                'photos' => [
                    'image/jpeg',
                    'image/jpeg',
                ],
            ],
            'size' => [
                'photos' => [
                    1024,
                    2048,
                ],
            ],
            'error' => [
                'photos' => [
                    \UPLOAD_ERR_OK,
                    \UPLOAD_ERR_OK,
                ],
            ],
            'tmp_name' => [
                'photos' => [
                    '/tmp/a',
                    '/tmp/b',
                ],
            ],
            'full_path' => [
                'photos' => [
                    'a.jpg',
                    'b.jpg',
                ],
            ],
        ];

        $files = (new EnvironmentUploadedFilesContext())->arrayOfFile('media.photos');

        self::assertCount(2, $files);
        self::assertSame('a.jpg', $files[0]->name);
        self::assertSame('b.jpg', $files[1]->name);
    }
}
