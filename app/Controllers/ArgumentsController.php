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
    public function show4(#[Argument(label: 'name')] ?int $value = null): ResponseInterface
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
    public function show6(#[Argument(label: 'name')] ?string $uuid = null): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($uuid),
        );
    }

    #[Route\Get(uri: '/args/optional-mixed/{?name}/{?age}')]
    public function show7(
        #[Argument(label: 'name')] bool $value = false,
        #[Argument(label: 'age')] ?int $age = null,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $age),
        );
    }

    #[Route\Get(uri: '/args/name-mixed/{name}/{country}')]
    public function show8(
        #[Argument(label: 'name')] string $value,
        #[Argument(label: 'country')] string $country
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $country),
        );
    }

    #[Route\Get(uri: '/args/regex-mixed/{name:\d+}/{active}')]
    public function show9(
        #[Argument(label: 'name')] int $value,
        #[Argument(label: 'active')] bool $active
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $active),
        );
    }

    #[Route\Get(uri: '/args/regex-optional-mixed/{?name:\d+}/{?score}')]
    public function show10(
        #[Argument(label: 'name')] ?int $value = null,
        #[Argument(label: 'score')] ?float $score = null
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $score),
        );
    }

    #[Route\Get(uri: '/args/explicit-mixed/{name<uuid>}/{region}')]
    public function show11(
        #[Argument(label: 'name')] string $uuid,
        #[Argument(label: 'region')] string $region
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($uuid, $region),
        );
    }

    #[Route\Get(uri: '/args/explicit-optional-mixed/{?name<uuid>}/{?timestamp:\d+}')]
    public function show12(
        #[Argument(label: 'name')] ?string $uuid = null,
        #[Argument(label: 'timestamp')] ?int $timestamp = null
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($uuid, $timestamp),
        );
    }
}
