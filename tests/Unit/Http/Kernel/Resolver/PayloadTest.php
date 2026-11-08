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

namespace Unit\Http\Kernel\Resolver;

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Reflection\StubParameterReflector;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\Resolver\Payload;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Validator\ValidationException;

class PayloadTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'payload_resolver_test_');
        $_POST = [];
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            \unlink($this->tempFile);
        }

        $_POST = [];
    }

    /**
     * @param class-string|null $defaultType
     */
    private function resolve(
        Payload $payload,
        string $contentType,
        string $body = '',
        ?string $defaultType = BodyContextFixture::class,
        ?string $builtinType = null,
    ): mixed {
        \file_put_contents($this->tempFile, $body);

        $serializers = SerializerManagerFactory::create();
        $container = new Container();

        $container->singleton(
            class: new Request(
                serializerManager: $serializers,
                headers: new StubHeaderContext(),
                files: new StubUploadedFilesContext(),
                body: new EnvironmentBodyContext(
                    serializerManager: $serializers,
                    streamInputSource: $this->tempFile,
                    contentType: $contentType,
                ),
                method: Method::POST,
            ),
        );

        return $payload->resolve(
            container: $container,
            parameter: new StubParameterReflector(
                defaultType: $defaultType,
                builtinType: $builtinType,
            ),
        );
    }

    public function testJsonBodyIsMappedToTheParameterType(): void
    {
        $result = $this->resolve(
            payload: new Payload(),
            contentType: 'application/json',
            body: '{"name":"John","age":30}',
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testJsonBodyWithKeyAndOnly(): void
    {
        $result = $this->resolve(
            payload: new Payload(
                key: 'data',
                only: [
                    'name',
                ],
            ),
            contentType: 'application/json; charset=utf-8',
            body: '{"data":{"name":"John","age":30}}',
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(0, $result->age);
    }

    public function testJsonListForArrayParameters(): void
    {
        $result = $this->resolve(
            payload: new Payload(
                className: BodyContextFixture::class,
            ),
            contentType: 'application/json',
            body: '[{"name":"John"},{"name":"Jane"}]',
            defaultType: null,
            builtinType: 'array',
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertInstanceOf(BodyContextFixture::class, $result[1]);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testFormBodyIsMappedLeniently(): void
    {
        $_POST = [
            'name' => 'John',
            'age' => '30',
        ];

        $result = $this->resolve(
            payload: new Payload(),
            contentType: 'application/x-www-form-urlencoded',
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame(30, $result->age);
    }

    public function testFormListUnderAKey(): void
    {
        $_POST['people'] = [
            [
                'name' => 'John',
            ],
        ];

        $result = $this->resolve(
            payload: new Payload(
                className: BodyContextFixture::class,
                key: 'people',
            ),
            contentType: 'multipart/form-data; boundary=x',
            defaultType: null,
            builtinType: 'array',
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);
    }

    public function testUnsupportedContentTypeIs415(): void
    {
        try {
            $this->resolve(
                payload: new Payload(),
                contentType: 'text/plain',
                body: 'name=John',
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::UNSUPPORTED_MEDIA_TYPE, $exception->responseCode);
        }
    }

    public function testMalformedInputIs400(): void
    {
        try {
            $this->resolve(
                payload: new Payload(),
                contentType: 'application/json',
                body: '{"name":',
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::BAD_REQUEST, $exception->responseCode);
            self::assertInstanceOf(SerializerInputException::class, $exception->getPrevious());
        }
    }

    public function testInvalidValuesBubbleUpAsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->resolve(
            payload: new Payload(),
            contentType: 'application/json',
            body: '{"quantity":-1}',
            defaultType: Validated::class,
        );
    }

    public function testUnknownKeysAreRejectedWhenNotSkipped(): void
    {
        try {
            $this->resolve(
                payload: new Payload(
                    skipUnknownKeys: false,
                ),
                contentType: 'application/json',
                body: '{"name":"John","admin":true}',
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertSame('admin', $exception->violations[0]->propertyPath);
        }
    }

    public function testMissingClassIs500(): void
    {
        try {
            $this->resolve(
                payload: new Payload(),
                contentType: 'application/json',
                body: '[]',
                defaultType: null,
                builtinType: 'array',
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $exception->responseCode);
        }
    }
}
