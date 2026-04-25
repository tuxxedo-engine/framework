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

use App\Models\User;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Request\Middleware\OutputCapture;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;

#[Controller(uri: '/model/')]
#[OutputCapture]
readonly class ModelController
{
    public function __construct(
        private ModelsManagerInterface $modelsManager,
    ) {
    }

    #[Route\Get]
    public function metaData(): ResponseInterface
    {
        \var_dump($this->modelsManager->metaData->getModel(User::class));

        return Response::empty(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get]
    public function fetch(): ResponseInterface
    {
        \var_dump(
            $this->modelsManager->findByIdentifier(User::class, 1),
            $this->modelsManager->findFirst(User::class),
        );

        return Response::empty(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get]
    public function fetchAll(): ResponseInterface
    {
        \var_dump(
            \iterator_to_array($this->modelsManager->findAll(User::class)),
        );

        return Response::empty(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }
}
