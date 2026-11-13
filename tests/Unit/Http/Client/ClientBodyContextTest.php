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

namespace Unit\Http\Client;

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\ClientBodyContext;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Validator\ValidationException;

class ClientBodyContextTest extends TestCase
{
    private function makeContext(
        StreamInterface|string $body = '',
        ?string $contentType = null,
    ): ClientBodyContext {
        return new ClientBodyContext(
            serializerManager: SerializerManagerFactory::create(),
            body: $body,
            contentType: $contentType,
        );
    }

    private static function stream(
        string $contents,
    ): ResourceStream {
        $stream = ResourceStream::fromTemporary();

        $stream->write($contents);
        $stream->rewind();

        return $stream;
    }

    public function testStringBody(): void
    {
        $context = $this->makeContext('hello');

        self::assertSame(
            [
                'hello',
                'hello',
                'hello',
            ],
            [
                $context->getRaw(),
                $context->getStream()->getContents(),
                $context->getStream()->getContents(),
            ],
        );

        self::assertSame(5, $context->size());
    }

    public function testStreamBodyIsReturnedAsIs(): void
    {
        $stream = self::stream('hello');

        self::assertSame($stream, $this->makeContext($stream)->getStream());
    }

    public function testStreamBodySize(): void
    {
        self::assertSame(5, $this->makeContext(self::stream('hello'))->size());
    }

    public function testRawReadsStreamBodyOnce(): void
    {
        $context = $this->makeContext(self::stream('hello'));

        self::assertSame(
            [
                'hello',
                'hello',
                'hello',
            ],
            [
                $context->getRaw(),
                $context->getRaw(),
                $context->getStream()->getContents(),
            ],
        );
    }

    public function testMediaTypes(): void
    {
        self::assertTrue($this->makeContext(contentType: 'application/json; charset=utf-8')->isJson());
        self::assertTrue($this->makeContext(contentType: 'application/problem+json')->isJson());
        self::assertTrue($this->makeContext(contentType: 'text/xml')->isXml());
        self::assertTrue($this->makeContext(contentType: 'application/atom+xml')->isXml());
        self::assertTrue($this->makeContext(contentType: 'application/xml')->isXml());
        self::assertTrue($this->makeContext(contentType: 'text/csv')->isCsv());
        self::assertTrue($this->makeContext(contentType: 'application/csv')->isCsv());
        self::assertTrue($this->makeContext(contentType: 'application/x-www-form-urlencoded')->isForm());
        self::assertTrue($this->makeContext(contentType: 'multipart/form-data; boundary=x')->isForm());
        self::assertTrue($this->makeContext(contentType: 'TEXT/PLAIN')->isText());
        self::assertFalse($this->makeContext(contentType: 'text/plain')->isJson());
        self::assertFalse($this->makeContext(contentType: 'text/plain')->isXml());
        self::assertFalse($this->makeContext(contentType: 'text/plain')->isCsv());
        self::assertFalse($this->makeContext(contentType: 'text/plain')->isForm());
        self::assertFalse($this->makeContext(contentType: 'application/json')->isText());
    }

    public function testMediaTypesWithoutContentType(): void
    {
        foreach (
            [
                $this->makeContext(),
                $this->makeContext(contentType: ' '),
            ] as $context
        ) {
            self::assertFalse($context->isJson());
            self::assertFalse($context->isXml());
            self::assertFalse($context->isCsv());
            self::assertFalse($context->isForm());
            self::assertFalse($context->isText());
        }
    }

    public function testGetJson(): void
    {
        self::assertSame(
            [
                'name' => 'John',
            ],
            $this->makeContext('{"name":"John"}')->getJson(associative: true),
        );
    }

    public function testJsonMap(): void
    {
        $result = $this->makeContext('{"data":{"name":"John","age":30}}')->jsonMap(BodyContextFixture::class, key: 'data');

        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testJsonMapList(): void
    {
        $result = $this->makeContext('[{"name":"John"},{"name":"Jane"}]')->jsonMapList(BodyContextFixture::class);

        self::assertCount(2, $result);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testXmlMap(): void
    {
        $result = $this->makeContext('<person><name>John</name><age>30</age></person>')->xmlMap(BodyContextFixture::class);

        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testXmlMapList(): void
    {
        $result = $this->makeContext('<people><person><name>John</name></person><person><name>Jane</name></person></people>')->xmlMapList(BodyContextFixture::class);

        self::assertCount(2, $result);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testCsvMapList(): void
    {
        $result = $this->makeContext("name;age\nJohn;30\n")->csvMapList(BodyContextFixture::class, separator: ';');

        self::assertCount(1, $result);
        self::assertSame('John', $result[0]->name);
        self::assertSame(30, $result[0]->age);
    }

    public function testMappingRequiresASerializerManager(): void
    {
        $context = new ClientBodyContext(
            serializerManager: null,
            body: '{}',
        );

        foreach (
            [
                static fn (): object => $context->jsonMap(BodyContextFixture::class),
                static fn (): array => $context->jsonMapList(BodyContextFixture::class),
                static fn (): object => $context->xmlMap(BodyContextFixture::class),
                static fn (): array => $context->xmlMapList(BodyContextFixture::class),
                static fn (): array => $context->csvMapList(BodyContextFixture::class),
            ] as $mapper
        ) {
            try {
                $mapper();

                self::fail('Expected HttpException was not thrown');
            } catch (HttpException $exception) {
                self::assertSame(500, $exception->responseCode->getStatusCode());
            }
        }

        self::assertSame('{}', $context->getRaw());
    }

    public function testSkipValidationIsPassedOn(): void
    {
        self::assertSame(-2, $this->makeContext('{"quantity":-2}')->jsonMap(Validated::class, skipValidation: true)->quantity);
        self::assertSame(-2, $this->makeContext('<v><quantity>-2</quantity></v>')->xmlMap(Validated::class, skipValidation: true)->quantity);
        self::assertSame(-2, $this->makeContext("quantity\n-2\n")->csvMapList(Validated::class, skipValidation: true)[0]->quantity);

        $this->expectException(ValidationException::class);

        $this->makeContext('{"quantity":-2}')->jsonMap(Validated::class);
    }
}
