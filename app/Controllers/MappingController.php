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

use App\Service\Entity\PersonOne;
use App\Service\Entity\PersonTwo;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Kernel\Resolver\Payload;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Serializer\SerializerManagerInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

readonly class MappingController
{
    public function __construct(
        private SerializerManagerInterface $serializers,
    ) {
    }

    #[Route(path: '/inputMap', method: [Method::GET, Method::POST])]
    public function inputMap(RequestInterface $request): ResponseInterface
    {
        if ($request->method === Method::GET) {
            return Response::html(
                html: '<form action="/inputMap" method="post">' .
                '<input type="text" name="struct[name]">' .
                '<br>' .
                '<input type="text" name="struct[age]">' .
                '<br><input type="submit">' .
                '</form>',
            );
        }

        return Response::json(
            json: $request->post->map(
                className: PersonTwo::class,
                key: 'struct',
            ),
        );
    }

    #[Route\Get(path: '/map')]
    public function map(): ResponseInterface
    {
        return Response::capture(
            callback: fn () => \var_dump(
                $this->serializers->array()->fromArray(
                    data: [
                        'name' => 'Engine',
                    ],
                    className: PersonOne::class,
                ),
                $this->serializers->array()->fromArrayList(
                    data: [
                        [
                            'name' => 'foo',
                        ],
                        [
                            'name' => 'bar',
                        ],
                        [
                            'name' => 'baz',
                        ],
                    ],
                    className: PersonOne::class,
                ),
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Post(path: '/mapTwo')]
    public function mapTwo(
        #[Payload(key: 'struct')] PersonTwo $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump($one),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(path: '/inputMapTwo')]
    public function inputMapTwo(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/mapTwo" method="post">' .
            '<input type="text" name="struct[name]">' .
            '<br>' .
            '<input type="text" name="struct[age]">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }

    /**
     * @param list<PersonTwo> $one
     */
    #[Route\Post(path: '/mapThree')]
    public function mapThree(
        #[Payload(className: PersonTwo::class, key: 'struct')] array $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump($one),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(path: '/inputMapThree')]
    public function inputMapThree(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/mapThree" method="post">' .
            '<input type="text" name="struct[0][name]">' .
            '<br>' .
            '<input type="text" name="struct[0][age]">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }

    #[Route\Post(path: '/mapFour')]
    public function mapFour(
        #[Payload(className: PersonOne::class, key: 'struct')] object $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump($one),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(path: '/inputMapFour')]
    public function inputMapFour(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/mapFour" method="post">' .
            '<input type="text" name="struct[name]">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }

    #[Route\Post(path: '/mapFive')]
    public function mapFive(
        #[Payload(key: 'struct')] PersonOne $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump($one),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(path: '/inputMapFive')]
    public function inputMapFive(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/mapFive" method="post">' .
            '<input type="text" name="struct[name]">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }

    #[Route\Post(path: '/mapSixA')]
    public function mapSixA(RequestInterface $request): ResponseInterface
    {
        return Response::capture(
            callback: fn () => \var_dump($request->post->jsonMap('struct', PersonTwo::class)),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Post(path: '/mapSixB')]
    public function mapSixB(RequestInterface $request): ResponseInterface
    {
        return Response::capture(
            callback: fn () => \var_dump($request->post->jsonMapList('struct', PersonTwo::class)),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(path: '/inputMapSix')]
    public function inputMapSix(): ViewInterface
    {
        return new View('map_json_form');
    }
}
