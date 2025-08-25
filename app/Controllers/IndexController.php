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

use App\Middleware\LoggerMiddleware;
use App\Middleware\OutputCaptureMiddleware;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Cookie;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Http\WeightedHeaderInterface;
use Tuxxedo\Mapper\Mapper;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Version;

#[Middleware(LoggerMiddleware::class)]
readonly class IndexController
{
    private MapperInterface $mapper;

    public function __construct(
        private ContainerInterface $container,
        private LoggerInterface $logger,
    ) {
        $this->mapper = new Mapper();
    }

    #[Route\Get(uri: '/')]
    public function index(): ResponseInterface
    {
        $this->container->resolve(LoggerInterface::class)->log('Inside action');

        return new Response(
            headers: [
                new Header('Content-Type', 'text/plain'),
            ],
            body: $this->logger->formatEntries(),
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

    #[Route\Get(uri: '/info')]
    public function info(): ResponseInterface
    {
        return Response::capture(
            \phpinfo(...),
        );
    }

    #[Route\Get(uri: '/json')]
    public function json(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                'method' => $request->server->method->name,
                'uri' => $request->server->uri,
                'https' => $request->server->https,
                'host' => $request->server->host,
                'port' => $request->server->port,
            ],
            prettyPrint: true,
        );
    }

    #[Route\Get(uri: '/cookies')]
    public function cookies(RequestInterface $request): ResponseInterface
    {
        $count = $request->cookies->has('count') ? $request->cookies->getInt('count') : 1;

        return Response::html(
            html: \sprintf(
                '<p>Visitor count: %d</p>',
                $count,
            ),
            headers: [
                new Cookie(
                    name: 'count',
                    value: (string) ++$count,
                    expires: \time() + 3600,
                ),
            ],
        );
    }

    #[Route\Get(uri: '/header')]
    public function header(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                $request->headers->get('Accept'),
                $request->headers->isWeighted('Accept'),
                $request->headers->getWeighted('Accept')->getWeightedPairs(),
            ],
        );
    }

    #[Route\Get(uri: '/headers')]
    public function headers(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                \array_map(
                    static fn (HeaderInterface $header): array => $header instanceof WeightedHeaderInterface
                        ? [
                            'name' => $header->name,
                            'value' => $header->value,
                            'weightedValue' => $header->getWeightedOrder(),
                        ]
                        : [
                            'name' => $header->name,
                            'value' => $header->value,
                        ],
                    $request->headers->all(),
                ),
            ],
            prettyPrint: true,
        );
    }

    #[Route\Get(uri: '/form')]
    public function form(): ResponseInterface
    {
        return Response::html(
            html: '<form action="/input" method="post"><input type="text" name="test"><input type="submit"></form>',
        );
    }

    #[Route\Post(uri: '/input')]
    public function input(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: [
                'string' => $request->post->getString('test'),
                'int' => $request->post->getInt('test'),
                'bool' => $request->post->getBool('test'),
                'floatDot' => $request->post->getFloat('test'),
                'floatComma' => $request->post->getFloat('test', decimalPoint: ',', thousandSeparator: '.'),
            ],
        );
    }

    #[Route(uri: '/inputMap', methods: [Method::GET, Method::POST])]
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

    #[Route(uri: '/fileUpload', methods: [Method::GET, Method::POST])]
    public function fileUpload(RequestInterface $request): ResponseInterface
    {
        if ($request->server->method === Method::GET) {
            return Response::html(
                html: '<form action="/fileUpload" method="post" enctype="multipart/form-data">' .
                      '<input type="file" name="uploadedFile">' .
                      '<br><input type="submit">' .
                      '</form>',
            );
        }

        $file = $request->files->get('uploadedFile');

        return Response::json(
            json: [
                'name' => $file->name,
                'type' => $file->type,
                'isTrustedType' => $file->isTrustedType(),
                'size' => $file->size,
                'temporaryPath' => $file->temporaryPath,
                'browserPath' => $file->browserPath,
                'contents' => $file->getContents(),
            ],
        );
    }

    #[Route\Get(uri: '/jsonBody')]
    public function jsonBody(RequestInterface $request): ResponseInterface
    {
        return Response::json(
            json: $request->body->getJson(),
        );
    }

    #[Route\Get(uri: '/phpinfo.php')]
    #[Middleware(OutputCaptureMiddleware::class)]
    public function phpInfo(RequestInterface $request): ResponseInterface
    {
        \phpinfo();

        return new Response();
    }

    #[Route\Get(uri: '/redirect')]
    public function redirect(RequestInterface $request): ResponseInterface
    {
        return Response::redirect('/');
    }

    #[Route\Get(uri: '/version')]
    public function version(RequestInterface $request): ResponseInterface
    {
        $versionInfo = [];
        $reflector = new \ReflectionClass(Version::class);
        $displayName = static fn (string $name): string => \str_replace(
            ' ',
            '',
            \lcfirst(
                \ucwords(
                    \str_replace(
                        '_',
                        ' ',
                        \strtolower($name),
                    ),
                ),
            ),
        );

        foreach ($reflector->getConstants() as $name => $value) {
            $versionInfo[$displayName($name)] = $value;
        }

        return Response::json(
            json: $versionInfo,
            prettyPrint: true,
        );
    }
}
