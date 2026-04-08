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

namespace App\Controllers;

use App\Service\Logger\CustomLoggerInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\Resolver\Tagged;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Logger\LoggerInterface;
use Tuxxedo\Router\Attribute\Route;

readonly class TaggedController
{
    public function __construct(
        ContainerInterface $container,
    ) {
        $container->persistent(CustomLoggerInterface::class);
    }

    /**
     * @param LoggerInterface[] $tagged
     */
    #[Route\Get(uri: '/tagged')]
    public function tagged(
        #[Tagged(LoggerInterface::class)] array $tagged,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump($tagged),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }
}
