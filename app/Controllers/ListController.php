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

use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\Http\Kernel\KernelInterface;
use Tuxxedo\Http\Kernel\Resolver\App;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Router\RouteInterface;
use Tuxxedo\Router\RouterInterface;

#[Controller(
    path: '/list',
    autoTrailingSlash: true,
)]
readonly class ListController
{
    private RouterInterface $router;

    public function __construct(
        #[App] KernelInterface $app,
        private EscaperInterface $escaper,
    ) {
        $this->router = $app->router;
    }

    #[Route\Get]
    public function index(): ResponseInterface
    {
        $routes = [];

        foreach ($this->router->getRoutes() as $route) {
            $method = $route->method === null
                ? 'any'
                : $route->method->name;

            $routes[$method] ??= [];
            $routes[$method][] = $route;
        }

        \ksort($routes);

        $html = '';
        $html .= '<table border="1">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #D0D0D0">';
        $html .= '<th><strong>Method</strong></th>';
        $html .= '<th><strong>Path</strong></th>';
        $html .= '<th><strong>Controller</strong></th>';
        $html .= '<th><strong>Action</strong></th>';
        $html .= '<th><strong>Priority</strong></th>';
        $html .= '<th><strong>Regex path</strong></th>';
        $html .= '<th><strong>Argument count</strong></th>';
        $html .= '<th><strong>Prefixed argument count</strong></th>';
        $html .= '<th><strong>Middleware count</strong></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($routes as $method => $items) {
            /** @var RouteInterface $route */
            foreach ($items as $route) {
                $path = $this->escaper->html($route->path);
                $regexPath = '<em>None</em>';

                if ($route->regexPath === null && ($method === 'any' || $method === 'GET')) {
                    $path = '<a href="' . $path . '">' . $path . '</a>';
                }

                if ($route->regexPath !== null) {
                    $regexPath = $route->regexPath;
                }

                $prefixedArgumentsCount = 0;

                foreach ($route->arguments as $argument) {
                    if ($argument->node->prefixed) {
                        $prefixedArgumentsCount++;
                    }
                }

                $html .= '<tr>';
                $html .= '<td>' . $method . '</td>';
                $html .= '<td>' . $path . '</td>';
                $html .= '<td>' . $route->controller . '</td>';
                $html .= '<td>' . $route->action . '</td>';
                $html .= '<td>' . $route->priority->name . '</td>';
                $html .= '<td>' . $regexPath . '</td>';
                $html .= '<td>' . \sizeof($route->arguments) . '</td>';
                $html .= '<td>' . $prefixedArgumentsCount . '</td>';
                $html .= '<td>' . \sizeof($route->middleware) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return new Response($html);
    }
}
