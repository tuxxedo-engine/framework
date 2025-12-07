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

use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Session\SessionInterface;

#[Controller(uri: '/session/')]
readonly class SessionController
{
    public function __construct(
        private SessionInterface $session,
    ) {
    }

    #[Route\Get]
    #[Route\Post]
    public function index(RequestInterface $request): ResponseInterface
    {
        if ($request->server->method === Method::POST) {
            $value = $request->post->getString('value');
            $value = match ($request->post->getString('type')) {
                'int' => \intval($value),
                'bool' => \boolval($value),
                'float' => \floatval($value),
                default => $value,
            };

            $this->session->set(
                name: $request->post->getString('name'),
                value: $value,
            );
        }

        $html = '';
        $html .= '<form action="/session/" method="post">';
        $html .= '<label><strong>Name:</strong>';
        $html .= '<input type="text" name="name"></label><br>';
        $html .= '<label><strong>Value:</strong>';
        $html .= '<input type="text" name="value"></label><br>';
        $html .= '<label><strong>Type:</strong>';
        $html .= '<select name="type"><option value="string">string</option>';
        $html .= '<option value="int">integer</option>';
        $html .= '<option value="bool">boolean</option>';
        $html .= '<option value="float">float</option></select></label><br>';
        $html .= '<input type="submit" value="Save">';
        $html .= '</form>';
        $html .= '<form action="/session/reset" method="post">';
        $html .= '<input type="submit" value="Reset">';
        $html .= '</form>';
        $html .= '<pre>' . \json_encode($this->session->all()) . '</pre>';

        return Response::html(
            html: $html,
        );
    }

    #[Route\Post]
    public function reset(): ResponseInterface
    {
        $this->session->adapter->unset();

        return Response::redirect(
            uri: '/session/',
        );
    }
}
