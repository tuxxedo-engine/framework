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

use Tuxxedo\Http\Request\Middleware\Csrf;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/csrf')]
readonly class CsrfController
{
    #[Route\Get(path: '/form')]
    public function form(): ViewInterface
    {
        return new View(
            name: 'csrf_form',
        );
    }

    #[Route\Post(path: '/form')]
    #[Middleware(Csrf::class)]
    public function formPost(RequestInterface $request): ResponseInterface
    {
        return Response::html(
            html: \sprintf(
                '<p style="color: green"><strong>Success:</strong> CSRF token was valid. Message: %s </p><a href="/csrf/form">Back</a>',
                \htmlspecialchars($request->post->string('message'), \ENT_QUOTES, 'UTF-8'),
            ),
        );
    }

    #[Route\Get(path: '/tamper')]
    public function tamper(): ViewInterface
    {
        return new View(
            name: 'csrf_tamper',
        );
    }

    #[Route\Post(path: '/tamper')]
    #[Middleware(Csrf::class)]
    public function tamperPost(): ResponseInterface
    {
        return Response::html(
            html: '<p style="color: green"><strong>Success:</strong> CSRF token was valid.</p><a href="/csrf/tamper">Back</a>',
        );
    }

    #[Route\Get(path: '/ajax')]
    public function ajax(): ViewInterface
    {
        return new View(
            name: 'csrf_ajax',
        );
    }

    #[Route\Post(path: '/ajax')]
    #[Middleware(Csrf::class)]
    public function ajaxPost(): ResponseInterface
    {
        return Response::json(
            json: [
                'success' => true,
                'message' => 'CSRF token was valid',
            ],
        );
    }

    #[Route\Get(path: '/ajax-tamper')]
    public function ajaxTamper(): ViewInterface
    {
        return new View(
            name: 'csrf_ajax_tamper',
        );
    }

    #[Route\Post(path: '/ajax-tamper')]
    #[Middleware(Csrf::class)]
    public function ajaxTamperPost(): ResponseInterface
    {
        return Response::json(
            json: [
                'success' => true,
                'message' => 'CSRF token was valid',
            ],
        );
    }
}
