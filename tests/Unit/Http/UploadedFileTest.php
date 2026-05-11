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

namespace Unit\Http;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\UploadedFile;

class UploadedFileTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'uploaded_file_test_');
        \file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            \unlink($this->tempFile);
        }
    }

    private function makeFile(
        string $temporaryPath = '/nonexistent/path',
        string $browserType = 'image/jpeg',
    ): UploadedFile {
        return new UploadedFile(
            name: 'test.jpg',
            browserType: $browserType,
            size: 1024,
            temporaryPath: $temporaryPath,
            browserPath: 'test.jpg',
        );
    }

    public function testTypeReturnsBrowserTypeWhenFileDoesNotExist(): void
    {
        self::assertSame('image/jpeg', $this->makeFile()->type);
    }

    public function testIsTrustedTypeReturnsFalseWhenFileDoesNotExist(): void
    {
        self::assertFalse($this->makeFile()->isTrustedType());
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testTypeIsResolvedByFinfoWhenFileExists(): void
    {
        self::assertSame('text/plain', $this->makeFile(temporaryPath: $this->tempFile)->type);
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testIsTrustedTypeReturnsTrueWhenFileExists(): void
    {
        self::assertTrue($this->makeFile(temporaryPath: $this->tempFile)->isTrustedType());
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testTypeIsCachedAfterFirstAccess(): void
    {
        $file = $this->makeFile(temporaryPath: $this->tempFile);

        self::assertSame($file->type, $file->type);
    }

    public function testGetContentsReturnsNullForNonExistentFile(): void
    {
        self::assertNull($this->makeFile()->getContents());
    }

    public function testGetContentsReturnsFileContents(): void
    {
        self::assertSame('test content', $this->makeFile(temporaryPath: $this->tempFile)->getContents());
    }

    public function testMoveToReturnsFalseForNonUploadedFile(): void
    {
        self::assertFalse($this->makeFile()->moveTo(\sys_get_temp_dir() . '/destination'));
    }
}
