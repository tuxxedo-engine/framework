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
use Tuxxedo\Escaper\EscaperInterface;
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

    #[Route(method: ['POST', 'GET'])]
    public function new(
        RequestInterface $request,
    ): ResponseInterface {
        if ($request->server->method === Method::POST) {
            $user = new User();
            $user->name = $request->post->getString('name');

            $this->modelsManager->save($user);

            return Response::html(
                \sprintf(
                    '<p>Created new user with id #%d',
                    $user->id,
                ),
            );
        }

        return Response::html(
            html: '<form action="/model/new" method="post">' .
            '<input type="text" name="name">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }

    #[Route(uri: 'update/{id<numeric-id>}', method: ['POST', 'GET'])]
    public function update(
        RequestInterface $request,
        EscaperInterface $escaper,
        #[Argument] int $id,
    ): ResponseInterface {
        $user = $this->modelsManager->findByIdentifier(User::class, $id) ?? throw HttpException::fromNotFound();

        if ($request->server->method === Method::POST) {
            $user->name = $request->post->getString('name');

            $this->modelsManager->save($user);

            return Response::html(
                \sprintf(
                    '<p>Updated user with id #%d',
                    $user->id,
                ),
            );
        }

        return Response::html(
            html: '<form action="/model/update/' . $user->id . '" method="post">' .
            '<input type="text" name="name" value="' . $escaper->attribute($user->name) . '">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }
}
