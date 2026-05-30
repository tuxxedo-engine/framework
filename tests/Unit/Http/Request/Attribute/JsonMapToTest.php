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
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Attribute\JsonMapTo;
use Tuxxedo\Http\Request\Request;

class JsonMapToTest extends TestCase
{
    private function makeContainer(
        RecordingInputContext $get = new RecordingInputContext(),
        RecordingInputContext $post = new RecordingInputContext(),
        Method $method = Method::GET,
    ): Container {
        $container = new Container();

        $container->persistent(
            class: new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: $get,
                post: $post,
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
                method: $method,
            ),
        );

        return $container;
    }

    public function testResolveDelegatesToInputJsonMapToForGet(): void
    {
        $fixture = new AttributeMappingFixture(
            name: 'John',
        );

        $get = new RecordingInputContext(
            jsonMapToReturn: $fixture,
        );

        $result = (new JsonMapTo(
            name: 'payload',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                get: $get,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertSame($fixture, $result);
        self::assertNotNull($get->jsonMapToCall);
        self::assertSame('payload', $get->jsonMapToCall['name']);
        self::assertSame(AttributeMappingFixture::class, $get->jsonMapToCall['className']);
        self::assertSame(0, $get->jsonMapToCall['flags']);
    }

    public function testResolvePassesFlagsToInputJsonMapTo(): void
    {
        $fixture = new AttributeMappingFixture();

        $get = new RecordingInputContext(
            jsonMapToReturn: $fixture,
        );

        (new JsonMapTo(
            name: 'payload',
            className: AttributeMappingFixture::class,
            flags: \JSON_BIGINT_AS_STRING,
        ))->resolve(
            container: $this->makeContainer(
                get: $get,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($get->jsonMapToCall);
        self::assertSame(\JSON_BIGINT_AS_STRING, $get->jsonMapToCall['flags']);
    }

    public function testResolveUsesExplicitContextOverMethod(): void
    {
        $fixture = new AttributeMappingFixture();

        $post = new RecordingInputContext(
            jsonMapToReturn: $fixture,
        );

        (new JsonMapTo(
            name: 'payload',
            className: AttributeMappingFixture::class,
            context: InputContext::POST,
        ))->resolve(
            container: $this->makeContainer(
                post: $post,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($post->jsonMapToCall);
    }

    public function testResolveFallsBackToParameterDefaultTypeWhenClassNameIsNull(): void
    {
        $fixture = new AttributeMappingFixture();

        $get = new RecordingInputContext(
            jsonMapToReturn: $fixture,
        );

        (new JsonMapTo('payload'))->resolve(
            container: $this->makeContainer(
                get: $get,
            ),
            parameter: new StubParameterReflector(
                defaultType: AttributeMappingFixture::class,
            ),
        );

        self::assertNotNull($get->jsonMapToCall);
        self::assertSame(AttributeMappingFixture::class, $get->jsonMapToCall['className']);
    }

    public function testResolveThrowsBadRequestWhenNeitherClassNameNorDefaultTypeAvailable(): void
    {
        $this->expectException(HttpException::class);

        (new JsonMapTo('payload'))->resolve(
            container: $this->makeContainer(),
            parameter: new StubParameterReflector(),
        );
    }

    public function testResolveWrapsContextExceptionsAsBadRequest(): void
    {
        $get = new RecordingInputContext();

        $get->throwOnMap = true;

        $this->expectException(HttpException::class);

        (new JsonMapTo(
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

        (new JsonMapTo(
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
