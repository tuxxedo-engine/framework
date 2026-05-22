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

namespace Unit\Http\Request\Attribute;

use Fixture\Http\Request\Attribute\AttributeMappingFixture;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\RecordingInputContext;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubServerContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Attribute\JsonMapToArrayOf;
use Tuxxedo\Http\Request\Request;

class JsonMapToArrayOfTest extends TestCase
{
    private function makeContainer(
        RecordingInputContext $get = new RecordingInputContext(),
        RecordingInputContext $post = new RecordingInputContext(),
        Method $method = Method::GET,
    ): Container {
        $container = new Container();
        $server = new StubServerContext();

        $server->method = $method;

        $container->persistent(
            class: new Request(
                server: $server,
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: $get,
                post: $post,
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
            ),
        );

        return $container;
    }

    public function testResolveDelegatesToInputJsonMapToArrayOf(): void
    {
        $items = [
            new AttributeMappingFixture(
                name: 'John',
            ),
            new AttributeMappingFixture(
                name: 'Jane',
            ),
        ];

        $get = new RecordingInputContext(
            jsonMapToArrayOfReturn: $items,
        );

        $result = (new JsonMapToArrayOf(
            name: 'payload',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer($get),
            parameter: new StubParameterReflector(),
        );

        self::assertSame($items, $result);
        self::assertNotNull($get->jsonMapToArrayOfCall);
        self::assertSame('payload', $get->jsonMapToArrayOfCall['name']);
        self::assertSame(AttributeMappingFixture::class, $get->jsonMapToArrayOfCall['className']);
        self::assertSame(0, $get->jsonMapToArrayOfCall['flags']);
    }

    public function testResolvePassesFlagsToInputJsonMapToArrayOf(): void
    {
        $get = new RecordingInputContext();

        (new JsonMapToArrayOf(
            name: 'payload',
            className: AttributeMappingFixture::class,
            flags: \JSON_BIGINT_AS_STRING,
        ))->resolve(
            container: $this->makeContainer($get),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($get->jsonMapToArrayOfCall);
        self::assertSame(\JSON_BIGINT_AS_STRING, $get->jsonMapToArrayOfCall['flags']);
    }

    public function testResolveUsesExplicitContextOverMethod(): void
    {
        $post = new RecordingInputContext();

        (new JsonMapToArrayOf(
            name: 'payload',
            className: AttributeMappingFixture::class,
            context: InputContext::POST,
        ))->resolve(
            container: $this->makeContainer(
                post: $post,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($post->jsonMapToArrayOfCall);
    }

    public function testResolveWrapsContextExceptionsAsBadRequest(): void
    {
        $get = new RecordingInputContext();

        $get->throwOnMap = true;

        $this->expectException(HttpException::class);

        (new JsonMapToArrayOf(
            name: 'payload',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer($get),
            parameter: new StubParameterReflector(),
        );
    }

    public function testResolveThrowsBadRequestWhenMethodHasNoInputContext(): void
    {
        $this->expectException(HttpException::class);

        (new JsonMapToArrayOf(
            name: 'payload',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                method: Method::DELETE,
            ),
            parameter: new StubParameterReflector(),
        );
    }
}
