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
    #[Route\Get(uri: '/args/optional/{?name}')]
    public function show(#[Argument(label: 'name')] bool $value = false): ResponseInterface
    {
        return Response::capture(
            callback: static fn () => var_dump($value),
        );
    }
}
