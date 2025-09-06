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

use App\Services\Logger\LoggerInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(uri: '/view/')]
readonly class ViewController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[Route\Get]
    public function hello(): ViewInterface
    {
        return new View(
            name: 'hello_world',
            scope: [
                'name' => 'Lumi',
            ],
        );
    }

    #[Route\Get]
    public function set(): ViewInterface
    {
        return new View(
            name: 'hello_world_set',
        );
    }

    #[Route\Get]
    public function call(): ViewInterface
    {
        return new View(
            name: 'hello_world_call',
        );
    }

    #[Route\Get]
    public function method(): ViewInterface
    {
        $this->logger->log('Testing method calls');

        return new View(
            name: 'hello_world_method',
            scope: [
                'logger' => $this->logger,
            ],
        );
    }

    #[Route\Get]
    public function include(): ViewInterface
    {
        return new View(
            name: 'hello_world_include',
        );
    }

    #[Route\Get]
    public function cond(): ViewInterface
    {
        return new View(
            name: 'hello_world_cond',
        );
    }

    #[Route\Get]
    public function while(): ViewInterface
    {
        return new View(
            name: 'hello_world_while',
        );
    }

    #[Route\Get]
    public function whileMore(): ViewInterface
    {
        return new View(
            name: 'hello_world_while_more',
        );
    }

    #[Route\Get]
    public function decl(): ViewInterface
    {
        return new View(
            name: 'hello_world_declare',
        );
    }

    #[Route\Get]
    public function for(): ViewInterface
    {
        return new View(
            name: 'hello_world_for',
            scope: [
                'iter' => \range(1, 5),
            ],
        );
    }

    #[Route\Get]
    public function seq(): ViewInterface
    {
        return new View(
            name: 'hello_world_seq',
        );
    }
}
