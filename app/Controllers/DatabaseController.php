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

use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;

#[Controller(uri: '/db/')]
readonly class DatabaseController
{
    public function __construct(
        private ConnectionManagerInterface $manager,
    ) {
    }

    #[Route\Get]
    public function index(): ResponseInterface
    {
        $this->manager->getDefaultConnection();

        return Response::html('ok!');
    }
}
