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
use Tuxxedo\File\FileInterface;
use Tuxxedo\File\LocalFile;
use Tuxxedo\Http\Client\Body\MultipartBody;

class MultipartBodyTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo-multipart-');

        if ($path === false) {
            self::fail('Failed to allocate temporary file path');
        }

        \file_put_contents($path, \str_repeat('x', 20000));

        $this->path = $path;
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function testContentTypeCarriesTheBoundary(): void
    {
        $body = new MultipartBody();

        self::assertMatchesRegularExpression('/^TuxxedoBoundary[0-9a-f]{32}$/', $body->boundary);
        self::assertSame('multipart/form-data; boundary=' . $body->boundary, $body->contentType);
    }

    public function testEncodesFieldsAndFiles(): void
    {
        $body = new MultipartBody(
            fields: [
                'userId' => '42',
            ],
            files: [
                'avatar' => new File('a.png', 'image/png', 'PNG'),
                'docs' => [
                    new File('one.txt', 'text/plain', 'one'),
                    new File(null, null, 'two'),
                ],
            ],
        );

        $stream = $body->encode(SerializerManagerFactory::create());
        $boundary = $body->boundary;
        $expected = "--{$boundary}\r\n" .
            "Content-Disposition: form-data; name=\"userId\"\r\n" .
            "\r\n" .
            "42\r\n" .
            "--{$boundary}\r\n" .
            "Content-Disposition: form-data; name=\"avatar\"; filename=\"a.png\"\r\n" .
            "Content-Type: image/png\r\n" .
            "\r\n" .
            "PNG\r\n" .
            "--{$boundary}\r\n" .
            "Content-Disposition: form-data; name=\"docs\"; filename=\"one.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "\r\n" .
            "one\r\n" .
            "--{$boundary}\r\n" .
            "Content-Disposition: form-data; name=\"docs\"; filename=\"docs\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "\r\n" .
            "two\r\n" .
            "--{$boundary}--\r\n";

        self::assertSame(\strlen($expected), $stream->getSize());
        self::assertSame($expected, $stream->getContents());
    }

    public function testStreamsPathFilesFromDisk(): void
    {
        $body = new MultipartBody(
            files: [
                'backup' => new LocalFile($this->path),
            ],
        );

        $stream = $body->encode(SerializerManagerFactory::create());
        $contents = $stream->getContents();

        self::assertStringContainsString('filename="' . \basename($this->path) . '"', $contents);
        self::assertStringContainsString(\str_repeat('x', 20000) . "\r\n--" . $body->boundary . "--\r\n", $contents);
        self::assertSame(\strlen($contents), $stream->getSize());
    }

    public function testEscapesNamesAndFilenames(): void
    {
        $body = new MultipartBody(
            fields: [
                "a\"b\r\nc" => 'value',
            ],
            files: [
                'file' => new File("evil\".txt\r\nX-Injected: 1", "text/plain\r\nX-Injected: 1", ''),
            ],
        );

        $contents = $body->encode(SerializerManagerFactory::create())->getContents();

        self::assertStringContainsString('name="a%22b%0D%0Ac"', $contents);
        self::assertStringContainsString('filename="evil%22.txt%0D%0AX-Injected: 1"', $contents);
        self::assertStringContainsString("Content-Type: text/plainX-Injected: 1\r\n", $contents);
        self::assertStringNotContainsString("\r\nX-Injected: 1", $contents);
    }

    public function testSizeIsUnknownWhenAFileSizeIsUnknown(): void
    {
        $file = new class () implements FileInterface {
            public ?string $name = 'unknown.bin';
            public ?string $mimeType = null;
            public ?int $size = null;

            public function contents(): string
            {
                return 'bytes';
            }
        };

        $stream = new MultipartBody(
            files: [
                'file' => $file,
            ],
        )->encode(SerializerManagerFactory::create());

        self::assertNull($stream->getSize());
        self::assertStringContainsString("bytes\r\n", $stream->getContents());
    }
}
