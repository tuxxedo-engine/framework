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

use App\Middleware\ValidUser;
use App\Models\User;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Middleware\OutputCapture;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Index;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

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

    // @todo Make a delete endpoint test and fix this name
    #[Route\Get(name: 'model.delete')]
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

    #[Route(method: ['POST', 'GET'], name: 'model.new')]
    public function new(
        RequestInterface $request,
    ): ViewInterface|ResponseInterface {
        if ($request->server->method === Method::POST) {
            $user = new User();
            $user->name = $request->post->getString('name');

            $this->modelsManager->save($user);

            return Response::redirect('/model/');
        }

        return new View(
            'model/new',
        );
    }

    #[Middleware(ValidUser::class)]
    #[Route(uri: 'update/{id<numeric-id>}', method: ['POST', 'GET'], name: 'model.update')]
    public function update(
        RequestInterface $request,
        #[Argument] int $id,
    ): ViewInterface|ResponseInterface {
        // @todo Consider fetch() + fetchByIdentifier() variants that throws on not found
        $user = $this->modelsManager->findByIdentifier(User::class, $id) ?? throw HttpException::fromNotFound();

        if ($request->server->method === Method::POST) {
            $user->name = $request->post->getString('name');

            $this->modelsManager->save($user);

            return Response::redirectRoute('model.list');
        }

        return new View(
            'model/update',
            [
                'user' => $user,
            ],
        );
    }

    #[Index]
    #[Route\Get(name: 'model.list')]
    public function list(): ViewInterface
    {
        return new View(
            'model/list',
            [
                'users' => $this->modelsManager->findAll(User::class),
            ],
        );
    }
}
