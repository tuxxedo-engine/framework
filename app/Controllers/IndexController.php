<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\M3;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Http\WeightedHeaderInterface;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Router\Attributes\Middleware;
use Tuxxedo\Router\Attributes\Route;

#[Middleware(M3::class)]
class IndexController
{
    public function __construct(
        private readonly Container $container,
        private readonly LoggerInterface $logger,
        private readonly MapperInterface $mapper,
    ) {
    }

    #[Route\Get(uri: '/')]
    public function index(): ResponseInterface
    {
        $this->container->resolve(LoggerInterface::class)->log('Inside action');

        return new Response(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
            body: $this->logger->formatEntries(),
        );
    }

    #[Route\Get(uri: '/map')]
    public function map(): ResponseInterface
    {
        return Response::capture(
            callback: fn () => var_dump(
                $this->mapper->mapArrayTo(
                    input: [
                        'name' => 'Engine',
                    ],
                    className: new class () {
                        public string $name = '';
                    },
                ),
                $this->mapper->mapToArrayOf(
                    input: [
                        [
                            'name' => 'foo',
                        ],
                        [
                            'name' => 'bar',
                        ],
                        [
                            'name' => 'baz',
                        ],
                    ],
                    className: new class () {
                        public string $name = '';
                    },
                ),
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(uri: '/info')]
    public function info(): ResponseInterface
    {
        return Response::capture(
            \phpinfo(...),
        );
    }

    #[Route\Get(uri: '/json')]
    public function json(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                'uri' => $request->context->uri,
                'https' => $request->context->https,
            ],
            prettyPrint: true,
        );
    }

    #[Route\Get(uri: '/cookies')]
    public function cookies(RequestInterface $request): ResponseInterface
    {
        $count = $request->cookies->has('count') ? $request->cookies->getInt('count') : 1;

        return Response::html(
            html: \sprintf(
                '<p>Visitor count: %d</p>',
                $count,
            ),
            headers: [
                new Cookie(
                    name: 'count',
                    value: (string) ++$count,
                    expires: \time() + 3600,
                ),
            ],
        );
    }

    #[Route\Get(uri: '/headers')]
    public function headers(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                \array_map(
                    static fn (HeaderInterface $header): array => $header instanceof WeightedHeaderInterface
                        ? [
                            'name' => $header->name,
                            'value' => $header->value,
                            'weightedValue' => $header->getWeightedOrder(),
                        ]
                        : [
                            'name' => $header->name,
                            'value' => $header->value,
                        ],
                    $request->headers->all(),
                ),
            ],
            prettyPrint: true,
        );
    }
}
