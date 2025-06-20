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

use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Router\Attributes\Route;

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
        \ob_start();
        var_dump(
            $this->mapper->mapArrayTo(
                input: [
                    'name' => 'Engine',
                ],
                className: new class {
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
                className: new class {
                    public string $name = '';
                },
            ),
        );

        return new Response(
            body: !\is_bool($body = \ob_get_clean()) ? $body : '',
        );
    }
}
