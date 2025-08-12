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

use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attributes\Controller;
use Tuxxedo\Router\Attributes\Route;
use Tuxxedo\View\Lumi\LumiEngine;

#[Controller(uri: '/lumi/')]
readonly class LumiController
{
    #[Route\Get]
    public function hello(): ResponseInterface
    {
        \ob_start();

        $engine = LumiEngine::createDefault();
        $engine->compileFile(__DIR__ . '/../views/lumi/hello_world.lumi');

        return new Response(
            body: !\is_bool($body = \ob_get_clean()) ? '<pre>' . \htmlspecialchars($body) . '</pre>' : '',
        );
    }
}
