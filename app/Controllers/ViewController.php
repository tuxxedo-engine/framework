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

use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\Router\Attributes\Controller;
use Tuxxedo\Router\Attributes\Route;

#[Controller(uri: '/view/')]
readonly class ViewController
{
    #[Route\Get]
    public function hello(): ViewInterface
    {
        return new View(
            name: 'hello',
            variables: [
                'message' => '<strong>Hello</strong> <em>World</em>',
            ],
        );
    }
}
