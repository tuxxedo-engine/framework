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

    public function testGetThrowsForMissingFile(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentUploadedFilesContext())->get('avatar');
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
    public function testGetThrowsForUploadError(
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

        (new EnvironmentUploadedFilesContext())->get('avatar');
    }

    public function testGetReturnsUploadedFile(): void
    {
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'error' => \UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test',
            'full_path' => 'photo.jpg',
        ];

        $file = (new EnvironmentUploadedFilesContext())->get('avatar');

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertSame('photo.jpg', $file->name);
        self::assertSame(1024, $file->size);
        self::assertSame('/tmp/test', $file->temporaryPath);
        self::assertSame('photo.jpg', $file->browserPath);
    }

    public function testGetWithDotNotationPath(): void
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

        $file = (new EnvironmentUploadedFilesContext())->get('gallery.0');

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertSame('photo.jpg', $file->name);
    }
}
