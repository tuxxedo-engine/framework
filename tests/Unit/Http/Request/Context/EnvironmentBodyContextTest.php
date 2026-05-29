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

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;

class EnvironmentBodyContextTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'body_context_test_');
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            \unlink($this->tempFile);
        }
    }

    private function makeContext(
        string $content,
    ): EnvironmentBodyContext {
        \file_put_contents($this->tempFile, $content);

        return new EnvironmentBodyContext(
            streamInputSource: $this->tempFile,
        );
    }

    public function testGetStreamIsReadable(): void
    {
        $stream = $this->makeContext('hello')->getStream();

        self::assertSame('hello', \stream_get_contents($stream));
    }

    public function testGetStreamThrowsForInvalidSource(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentBodyContext(streamInputSource: '/nonexistent/path'))->getStream();
    }

    public function testGetRawReadsStreamContent(): void
    {
        self::assertSame('hello world', $this->makeContext('hello world')->getRaw());
    }

    public function testGetJsonDecodesJsonObject(): void
    {
        $result = $this->makeContext('{"key":"value"}')->getJson();

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('value', $result->key);
    }

    public function testGetJsonAssociativeReturnsArray(): void
    {
        $result = $this->makeContext('{"key":"value"}')->getJson(
            associative: true,
        );

        self::assertSame(
            [
                'key' => 'value',
            ],
            $result,
        );
    }

    public function testJsonMapToMapsObjectJson(): void
    {
        $result = $this->makeContext('{"name":"John","age":30}')->jsonMapTo(
            className: BodyContextFixture::class,
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testJsonMapToMapsFromJsonArray(): void
    {
        $result = $this->makeContext('[]')->jsonMapTo(
            className: BodyContextFixture::class,
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
    }

    public function testJsonMapToThrowsForScalarJson(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext('"just a string"')->jsonMapTo(
            className: BodyContextFixture::class,
        );
    }

    public function testJsonMapToArrayOfMapsArrayJson(): void
    {
        $result = $this->makeContext(
            '[{"name":"John","age":30},{"name":"Jane","age":25}]',
        )->jsonMapToArrayOf(
            className: BodyContextFixture::class,
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(BodyContextFixture::class, $result[0]);
        self::assertSame('John', $result[0]->name);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testJsonMapToArrayOfThrowsForNonArray(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext('{"name":"John"}')->jsonMapToArrayOf(
            className: BodyContextFixture::class,
        );
    }

    private function makeContextWithContentType(
        string $contentType,
    ): EnvironmentBodyContext {
        return new EnvironmentBodyContext(
            contentType: $contentType,
        );
    }

    public function testIsJsonReturnsTrueForApplicationJson(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/json')->isJson());
    }

    public function testIsJsonReturnsTrueForApplicationJsonWithCharset(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/json; charset=utf-8')->isJson());
    }

    public function testIsJsonReturnsTrueForStructuredSuffix(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/vnd.api+json')->isJson());
        self::assertTrue($this->makeContextWithContentType('application/problem+json')->isJson());
        self::assertTrue($this->makeContextWithContentType('application/ld+json')->isJson());
    }

    public function testIsJsonIsCaseInsensitive(): void
    {
        self::assertTrue($this->makeContextWithContentType('Application/JSON')->isJson());
    }

    public function testIsJsonTrimsWhitespace(): void
    {
        self::assertTrue($this->makeContextWithContentType('  application/json  ')->isJson());
    }

    public function testIsJsonReturnsFalseForOtherMediaTypes(): void
    {
        self::assertFalse($this->makeContextWithContentType('text/html')->isJson());
        self::assertFalse($this->makeContextWithContentType('application/jsonp')->isJson());
        self::assertFalse($this->makeContextWithContentType('application/xml')->isJson());
    }

    public function testIsJsonReturnsFalseForMissingContentType(): void
    {
        self::assertFalse((new EnvironmentBodyContext(contentType: ''))->isJson());
    }

    public function testIsJsonReturnsFalseWhenServerContentTypeMissing(): void
    {
        unset($_SERVER['CONTENT_TYPE']);

        self::assertFalse((new EnvironmentBodyContext())->isJson());
    }

    public function testIsJsonReadsFromServerContentTypeWhenNoConstructorOverride(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        try {
            self::assertTrue((new EnvironmentBodyContext())->isJson());
        } finally {
            unset($_SERVER['CONTENT_TYPE']);
        }
    }

    public function testIsXmlReturnsTrueForApplicationXml(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/xml')->isXml());
    }

    public function testIsXmlReturnsTrueForTextXml(): void
    {
        self::assertTrue($this->makeContextWithContentType('text/xml')->isXml());
    }

    public function testIsXmlReturnsTrueForStructuredSuffix(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/atom+xml')->isXml());
        self::assertTrue($this->makeContextWithContentType('application/rss+xml')->isXml());
    }

    public function testIsXmlReturnsTrueWithCharsetParameter(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/xml; charset=utf-8')->isXml());
    }

    public function testIsXmlReturnsFalseForOtherMediaTypes(): void
    {
        self::assertFalse($this->makeContextWithContentType('application/json')->isXml());
        self::assertFalse($this->makeContextWithContentType('text/html')->isXml());
    }

    public function testIsXmlReturnsFalseForMissingContentType(): void
    {
        self::assertFalse((new EnvironmentBodyContext(contentType: ''))->isXml());
    }

    public function testIsFormReturnsTrueForUrlEncoded(): void
    {
        self::assertTrue($this->makeContextWithContentType('application/x-www-form-urlencoded')->isForm());
    }

    public function testIsFormReturnsTrueForMultipart(): void
    {
        self::assertTrue($this->makeContextWithContentType('multipart/form-data')->isForm());
    }

    public function testIsFormReturnsTrueForMultipartWithBoundary(): void
    {
        self::assertTrue($this->makeContextWithContentType('multipart/form-data; boundary=----WebKitFormBoundary')->isForm());
    }

    public function testIsFormIsCaseInsensitive(): void
    {
        self::assertTrue($this->makeContextWithContentType('Application/X-WWW-Form-UrlEncoded')->isForm());
    }

    public function testIsFormReturnsFalseForOtherMediaTypes(): void
    {
        self::assertFalse($this->makeContextWithContentType('application/json')->isForm());
        self::assertFalse($this->makeContextWithContentType('text/plain')->isForm());
    }

    public function testIsFormReturnsFalseForMissingContentType(): void
    {
        self::assertFalse((new EnvironmentBodyContext(contentType: ''))->isForm());
    }

    public function testIsTextReturnsTrueForTextPlain(): void
    {
        self::assertTrue($this->makeContextWithContentType('text/plain')->isText());
    }

    public function testIsTextReturnsTrueForTextHtml(): void
    {
        self::assertTrue($this->makeContextWithContentType('text/html')->isText());
    }

    public function testIsTextReturnsTrueForAnyTextSubtype(): void
    {
        self::assertTrue($this->makeContextWithContentType('text/csv')->isText());
        self::assertTrue($this->makeContextWithContentType('text/markdown')->isText());
        self::assertTrue($this->makeContextWithContentType('text/xml')->isText());
    }

    public function testIsTextReturnsTrueWithCharsetParameter(): void
    {
        self::assertTrue($this->makeContextWithContentType('text/plain; charset=utf-8')->isText());
    }

    public function testIsTextReturnsFalseForNonTextTypes(): void
    {
        self::assertFalse($this->makeContextWithContentType('application/json')->isText());
        self::assertFalse($this->makeContextWithContentType('image/png')->isText());
    }

    public function testIsTextReturnsFalseForMissingContentType(): void
    {
        self::assertFalse((new EnvironmentBodyContext(contentType: ''))->isText());
    }
}
