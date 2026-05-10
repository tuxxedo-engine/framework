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

namespace Unit\View;

use PHPUnit\Framework\TestCase;
use Support\View\RecordingViewRender;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewRenderInterface;

class ViewTest extends TestCase
{
    public function testConstructorExposesAllProperties(): void
    {
        $view = new View(
            name: 'home',
            scope: [
                'title' => 'Welcome',
            ],
            responseCode: ResponseCode::ACCEPTED,
        );

        self::assertSame('home', $view->name);
        self::assertSame(
            [
                'title' => 'Welcome',
            ],
            $view->scope,
        );
        self::assertSame(ResponseCode::ACCEPTED, $view->responseCode);
    }

    public function testConstructorDefaultsScopeToEmptyArray(): void
    {
        $view = new View(
            name: 'home',
        );

        self::assertSame([], $view->scope);
    }

    public function testConstructorDefaultsResponseCodeToOk(): void
    {
        $view = new View(
            name: 'home',
        );

        self::assertSame(ResponseCode::OK, $view->responseCode);
    }

    public function testToResponseReturnsResponseWithRenderedBody(): void
    {
        $renderer = new RecordingViewRender(
            output: 'rendered-body',
        );

        $container = new Container();
        $container->persistent($renderer);
        $container->alias(ViewRenderInterface::class, $renderer::class);

        $view = new View(
            name: 'home',
        );

        $response = $view->toResponse($container);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('rendered-body', $response->body);
    }

    public function testToResponsePropagatesResponseCode(): void
    {
        $renderer = new RecordingViewRender();

        $container = new Container();
        $container->persistent($renderer);
        $container->alias(ViewRenderInterface::class, $renderer::class);

        $view = new View(
            name: 'home',
            responseCode: ResponseCode::CREATED,
        );

        $response = $view->toResponse($container);

        self::assertSame(ResponseCode::CREATED, $response->responseCode);
    }

    public function testToResponseRendersTheCurrentView(): void
    {
        $renderer = new RecordingViewRender();

        $container = new Container();
        $container->persistent($renderer);
        $container->alias(ViewRenderInterface::class, $renderer::class);

        $view = new View(
            name: 'profile',
            scope: [
                'user' => 'kalle',
            ],
        );

        $view->toResponse($container);

        self::assertSame($view, $renderer->lastView);
    }
}
