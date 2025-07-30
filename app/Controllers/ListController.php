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

use Tuxxedo\Container\Resolvers\App;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attributes\Route;
use Tuxxedo\Router\RouterInterface;

#[Route\Controller(uri: '/list/')]
readonly class ListController
{
    private RouterInterface $router;

    public function __construct(
        #[App] Kernel $app,
    ) {
        $this->router = $app->router;
    }

    #[Route\Get]
    public function index(RequestInterface $request): ResponseInterface
    {
        $routes = [];

        foreach ($this->router->routes as $route) {
            $method = $route->method === null ? 'any' : $route->method->name;

            $routes[$method] ??= [];
            $routes[$method][] = $route;
        }

        \ksort($routes);

        $html = '';
        $html .= '<table border="1">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #D0D0D0">';
        $html .= '<th><strong>Method</strong></th>';
        $html .= '<th><strong>URI</strong></th>';
        $html .= '<th><strong>Controller</strong></th>';
        $html .= '<th><strong>Action</strong></th>';
        $html .= '<th><strong>Priority</strong></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($routes as $method => $items) {
            foreach ($items as $route) {
                $uri = $route->uri;

                if ($method === 'any' || $method === 'GET') {
                    $uri = '<a href="' . $uri . '">' . $uri . '</a>';
                }

                $html .= '<tr>';
                $html .= '<td>' . $method . '</td>';
                $html .= '<td>' . $uri . '</td>';
                $html .= '<td>' . $route->controller . '</td>';
                $html .= '<td>' . $route->action . '</td>';
                $html .= '<td>' . $route->priority->name . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return new Response($html);
    }
}
