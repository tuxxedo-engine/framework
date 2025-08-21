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
        $viewFile = __DIR__ . '/../views/lumi/hello_world_set.lumi';
        $contents = @\file_get_contents($viewFile);

        $buffer = '';
        $buffer .= '<h3>Source</h3>';
        $buffer .= '<pre>' . \htmlspecialchars($contents !== false ? $contents : '') . '</pre>';
        $buffer .= '<h3>Compiled Source</h3>';
        $buffer .= '<pre>' . \htmlspecialchars(LumiEngine::createDefault()->compileFile($viewFile)->source) . '</pre>';

        return Response::html($buffer);
    }
}
