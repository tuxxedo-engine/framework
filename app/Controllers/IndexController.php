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
use Tuxxedo\Container\Resolvers\AppContainer;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;

class IndexController
{
    public function __construct(
        #[AppContainer] private readonly Container $container,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function index(): ResponseInterface
    {
        $this->container->resolve(LoggerInterface::class)->log('Inside action');

        return new Response(
            body: $this->logger->formatEntries(),
        );
    }
}
