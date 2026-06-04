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

namespace Fixture\Router\RouteDiscoverer\Discovery\HttpManualMethods;

use Tuxxedo\Http\Method;
use Tuxxedo\Router\Attribute\Route;

class HttpManualMethodController
{
    #[Route(path: '/get', method: ['', 'GET', Method::GET])]
    public function getMethod(): void
    {
    }

    #[Route(path: '/head', method: ['HEAD', Method::HEAD])]
    public function headMethod(): void
    {
    }

    #[Route(path: '/post', method: ['POST', Method::POST])]
    public function postMethod(): void
    {
    }

    #[Route(path: '/put', method: ['PUT', Method::PUT])]
    public function putMethod(): void
    {
    }

    #[Route(path: '/delete', method: ['DELETE', Method::DELETE])]
    public function deleteMethod(): void
    {
    }

    #[Route(path: '/connect', method: ['CONNECT', Method::CONNECT])]
    public function connectMethod(): void
    {
    }

    #[Route(path: '/options', method: ['OPTIONS', Method::OPTIONS])]
    public function optionsMethod(): void
    {
    }

    #[Route(path: '/trace', method: ['TRACE', Method::TRACE])]
    public function traceMethod(): void
    {
    }

    #[Route(path: '/patch', method: ['PATCH', Method::PATCH])]
    public function patchMethod(): void
    {
    }
}
