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

use App\Service\Entity\PersonOne;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Attribute\MapTo;
use Tuxxedo\Http\Request\Attribute\MapToArrayOf;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Router\Attribute\Route;

readonly class MappingController
{
    public function __construct(
        private MapperInterface $mapper = new Mapper(),
    ) {
    }

    #[Route(uri: '/inputMap', method: [Method::GET, Method::POST])]
    public function inputMap(RequestInterface $request): ResponseInterface
    {
        if ($request->server->method === Method::GET) {
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
            json: $request->post->mapTo(
                'struct',
                new class () {
                    public string $name;
                    public int $age;
                },
            ),
        );
    }

    #[Route\Get(uri: '/map')]
    public function map(): ResponseInterface
    {
        return Response::capture(
            callback: fn () => \var_dump(
                $this->mapper->mapArrayTo(
                    input: [
                        'name' => 'Engine',
                    ],
                    className: new class () {
                        public string $name = '';
                    },
                ),
                $this->mapper->mapToArrayOf(
                    input: [
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
                    className: new class () {
                        public string $name = '';
                    },
                ),
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Post(uri: '/mapTwo')]
    public function mapTwo(
        #[MapTo\Post(
            name: 'struct',
            className: static function (): object {
                return new class () {
                    public string $name;
                    public int $age;
                };
            },
        )] object $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump(
                $one,
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(uri: '/inputMapTwo')]
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
     * @param array<object{name: string, age: int}> $one
     */
    #[Route\Post(uri: '/mapThree')]
    public function mapThree(
        #[MapToArrayOf\Post(
            name: 'struct',
            className: static function (): object {
                return new class () {
                    public string $name;
                    public int $age;
                };
            },
        )] array $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump(
                $one,
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(uri: '/inputMapThree')]
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

    #[Route\Post(uri: '/mapFour')]
    public function mapFour(
        #[MapTo\Post(name: 'struct', className: PersonOne::class)] object $one,
    ): ResponseInterface {
        return Response::capture(
            callback: fn () => \var_dump(
                $one,
            ),
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
        );
    }

    #[Route\Get(uri: '/inputMapFour')]
    public function inputMapFour(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/mapFour" method="post">' .
            '<input type="text" name="struct[name]">' .
            '<br><input type="submit">' .
            '</form>',
        );
    }
}
