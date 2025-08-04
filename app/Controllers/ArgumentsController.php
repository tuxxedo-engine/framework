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
use Tuxxedo\Router\Attributes\Controller;
use Tuxxedo\Router\Attributes\Route;

#[Controller(uri: '/args/')]
readonly class ArgumentsController
{
    #[Route\Get(uri: 'optional/{?name}')]
    public function show1(#[Argument(label: 'name')] bool $value = false): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: 'name/{name}')]
    public function show2(#[Argument(label: 'name')] string $value): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: 'regex/{name:\d+}')]
    public function show3(#[Argument(label: 'name')] int $value): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: 'regex-optional/{?name:\d+}')]
    public function show4(#[Argument(label: 'name')] ?int $value = null): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: 'explicit/{name<uuid>}')]
    public function show5(#[Argument(label: 'name')] string $uuid): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($uuid),
        );
    }

    #[Route\Get(uri: 'explicit-optional/{?name<uuid>}')]
    public function show6(#[Argument(label: 'name')] ?string $uuid = null): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($uuid),
        );
    }

    #[Route\Get(uri: 'optional-mixed/{?name}/{?age}')]
    public function show7(
        #[Argument(label: 'name')] bool $value = false,
        #[Argument(label: 'age')] ?int $age = null,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $age),
        );
    }

    #[Route\Get(uri: 'name-mixed/{name}/{country}')]
    public function show8(
        #[Argument(label: 'name')] string $value,
        #[Argument(label: 'country')] string $country
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $country),
        );
    }

    #[Route\Get(uri: 'regex-mixed/{name:\d+}/{active}')]
    public function show9(
        #[Argument(label: 'name')] int $value,
        #[Argument(label: 'active')] bool $active,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $active),
        );
    }

    #[Route\Get(uri: 'regex-optional-mixed/{?name:\d+}/{?score}')]
    public function show10(
        #[Argument(label: 'name')] ?int $value = null,
        #[Argument(label: 'score')] ?float $score = null,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value, $score),
        );
    }

    #[Route\Get(uri: 'explicit-mixed/{name<uuid>}/{region}')]
    public function show11(
        #[Argument(label: 'name')] string $uuid,
        #[Argument(label: 'region')] string $region,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($uuid, $region),
        );
    }

    #[Route\Get(uri: 'explicit-optional-mixed/{?name<uuid>}/{?timestamp:\d+}')]
    public function show12(
        #[Argument(label: 'name')] ?string $uuid = null,
        #[Argument(label: 'timestamp')] ?int $timestamp = null,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($uuid, $timestamp),
        );
    }

    #[Route\Get(uri: 'textual/{alpha<alpha>}/{slug<slug>}')]
    public function show13(
        #[Argument(label: 'alpha')] string $alpha,
        #[Argument(label: 'slug')] string $slug,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($alpha, $slug),
        );
    }

    #[Route\Get(uri: 'primitive/{bool<bool>}')]
    public function show14(
        #[Argument(label: 'bool')] bool $value,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }

    #[Route\Get(uri: 'locale/{country<country-code>}/{currency<currency-code>}/{language<language-code>}')]
    public function show15(
        #[Argument(label: 'country')] string $country,
        #[Argument(label: 'currency')] string $currency,
        #[Argument(label: 'language')] string $language,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($country, $currency, $language),
        );
    }

    #[Route\Get(uri: 'temporal/{date<date>}/{timestamp<timestamp>}')]
    public function show16(
        #[Argument(label: 'date')] string $date,
        #[Argument(label: 'timestamp')] int $timestamp,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($date, $timestamp),
        );
    }

    #[Route\Get(uri: 'encoding/{hex<hex>}')]
    public function show17(
        #[Argument(label: 'hex')] string $hex,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($hex),
        );
    }

    #[Route\Get(uri: 'hash/{sha1<sha1>}/{sha256<sha256>}')]
    public function show18(
        #[Argument(label: 'sha1')] string $sha1,
        #[Argument(label: 'sha256')] string $sha256,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($sha1, $sha256),
        );
    }

    #[Route\Get(uri: 'identifier/{id<numeric-id>}/{uuid<uuid>}/{uuidv4<uuid-v4>}')]
    public function show19(
        #[Argument(label: 'id')] int $id,
        #[Argument(label: 'uuid')] string $uuid,
        #[Argument(label: 'uuidv4')] string $uuidv4,
    ): ResponseInterface {
        return Response::capture(
            callback: static fn () => var_dump($id, $uuid, $uuidv4),
        );
    }
}
