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
use Tuxxedo\Http\Request\Attribute\MapTo;
use Tuxxedo\Http\Request\Request;

class MapToTest extends TestCase
{
    private function makeContainer(
        RecordingInputContext $get = new RecordingInputContext(),
        RecordingInputContext $post = new RecordingInputContext(),
        Method $method = Method::GET,
    ): Container {
        $container = new Container();

        $container->singleton(
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

    public function testResolveUsesGetContextForGetMethod(): void
    {
        $fixture = new AttributeMappingFixture(
            name: 'John',
        );

        $get = new RecordingInputContext(
            mapToReturn: $fixture,
        );

        $result = (new MapTo(
            name: 'user',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer($get),
            parameter: new StubParameterReflector(),
        );

        self::assertSame($fixture, $result);
        self::assertNotNull($get->mapToCall);
        self::assertSame('user', $get->mapToCall['name']);
        self::assertSame(AttributeMappingFixture::class, $get->mapToCall['className']);
    }

    public function testResolveUsesPostContextForPostMethod(): void
    {
        $fixture = new AttributeMappingFixture();

        $post = new RecordingInputContext(
            mapToReturn: $fixture,
        );

        (new MapTo(
            name: 'user',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                post: $post,
                method: Method::POST,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($post->mapToCall);
        self::assertSame('user', $post->mapToCall['name']);
    }

    public function testResolveUsesExplicitContextOverMethod(): void
    {
        $fixture = new AttributeMappingFixture();

        $post = new RecordingInputContext(
            mapToReturn: $fixture,
        );

        (new MapTo(
            name: 'user',
            className: AttributeMappingFixture::class,
            context: InputContext::POST,
        ))->resolve(
            container: $this->makeContainer(
                post: $post,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($post->mapToCall);
    }

    public function testResolveFallsBackToParameterDefaultTypeWhenClassNameIsNull(): void
    {
        $fixture = new AttributeMappingFixture();

        $get = new RecordingInputContext(
            mapToReturn: $fixture,
        );

        (new MapTo('user'))->resolve(
            container: $this->makeContainer(
                get: $get,
            ),
            parameter: new StubParameterReflector(
                defaultType: AttributeMappingFixture::class,
            ),
        );

        self::assertNotNull($get->mapToCall);
        self::assertSame(AttributeMappingFixture::class, $get->mapToCall['className']);
    }

    public function testResolveThrowsBadRequestWhenNeitherClassNameNorDefaultTypeAvailable(): void
    {
        $this->expectException(HttpException::class);

        (new MapTo('user'))->resolve(
            container: $this->makeContainer(),
            parameter: new StubParameterReflector(),
        );
    }

    public function testResolveWrapsContextExceptionsAsBadRequest(): void
    {
        $get = new RecordingInputContext();
        $get->throwOnMap = true;

        $this->expectException(HttpException::class);

        (new MapTo(
            name: 'user',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                get: $get,
            ),
            parameter: new StubParameterReflector(),
        );
    }

    public function testResolveThrowsBadRequestWhenMethodHasNoInputContext(): void
    {
        $this->expectException(HttpException::class);

        (new MapTo(
            name: 'user',
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                method: Method::DELETE,
            ),
            parameter: new StubParameterReflector(),
        );
    }
}
