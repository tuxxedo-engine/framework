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

namespace Fixture\Router\RouteDiscoverer\Discovery\HttpMethods;

use Tuxxedo\Router\Attribute\Route\Connect;
use Tuxxedo\Router\Attribute\Route\Delete;
use Tuxxedo\Router\Attribute\Route\Get;
use Tuxxedo\Router\Attribute\Route\Head;
use Tuxxedo\Router\Attribute\Route\Options;
use Tuxxedo\Router\Attribute\Route\Patch;
use Tuxxedo\Router\Attribute\Route\Post;
use Tuxxedo\Router\Attribute\Route\Put;
use Tuxxedo\Router\Attribute\Route\Trace;

class HttpMethodController
{
    #[Get(uri: '/get')]
    public function getMethod(): void
    {
    }

    #[Head(uri: '/head')]
    public function headMethod(): void
    {
    }

    #[Post(uri: '/post')]
    public function postMethod(): void
    {
    }

    #[Put(uri: '/put')]
    public function putMethod(): void
    {
    }

    #[Delete(uri: '/delete')]
    public function deleteMethod(): void
    {
    }

    #[Connect(uri: '/connect')]
    public function connectMethod(): void
    {
    }

    #[Options(uri: '/options')]
    public function optionsMethod(): void
    {
    }

    #[Trace(uri: '/trace')]
    public function traceMethod(): void
    {
    }

    #[Patch(uri: '/patch')]
    public function patchMethod(): void
    {
    }
}
