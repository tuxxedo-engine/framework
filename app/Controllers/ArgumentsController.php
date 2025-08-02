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

use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attributes\Argument;
use Tuxxedo\Router\Attributes\Route;

readonly class ArgumentsController
{
    #[Route\Get(uri: '/args/optional/{?name}')]
    public function show1(#[Argument(label: 'name')] bool $value = false): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: '/args/name/{name}')]
    public function show2(#[Argument(label: 'name')] string $value): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: '/args/regex/{name:\d+}')]
    public function show3(#[Argument(label: 'name')] int $value): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: '/args/regex-optional/{?name:\d+}')]
    public function show4(#[Argument(label: 'name')] ?int $value): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }
    #[Route\Get(uri: '/args/explicit/{name<uuid>}')]
    public function show5(#[Argument(label: 'name')] string $uuid): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($uuid),
        );
    }

    #[Route\Get(uri: '/args/explicit-optional/{?name<uuid>}')]
    public function show6(#[Argument(label: 'name')] ?string $uuid): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($uuid),
        );
    }
}
