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

namespace Fixture\Http\Kernel;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Response\ResponsableInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

class DispatcherController
{
    public function index(): ResponseInterface
    {
        return new Response(
            body: 'dispatched',
        );
    }

    public function responsable(): ResponsableInterface
    {
        return new class () implements ResponsableInterface {
            public ResponseCode $responseCode {
                get => ResponseCode::OK;
            }

            public function toResponse(
                ContainerInterface $container,
            ): ResponseInterface {
                return new Response(body: 'from responsable');
            }
        };
    }

    public function show(
        int $id,
    ): ResponseInterface {
        return new Response(body: (string) $id);
    }

    public function returnsNull(): mixed
    {
        return null;
    }
}
