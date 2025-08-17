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
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\Lumi\LumiEngine;

#[Controller(uri: '/lumi/')]
readonly class LumiController
{
    #[Route\Get]
    public function hello(): ResponseInterface
    {
        \ob_start();
        $viewFile = __DIR__ . '/../views/lumi/hello_world.lumi';
        $contents = @\file_get_contents($viewFile);

        $buffer = '<h3>Source</h3><pre>' . \htmlspecialchars($contents !== false ? $contents : '') . '</pre><h3>Tokens</h3>';

        $engine = LumiEngine::createDefault();
        $engine->compileFile($viewFile);

        return new Response(
            body: $buffer . (!\is_bool($body = \ob_get_clean()) ? '<pre>' . \htmlspecialchars($body) . '</pre>' : ''),
        );
    }
}
