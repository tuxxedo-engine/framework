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
        $a = new \stdClass();
        $a->b = null;
        $a->c = new \stdClass();
        $a->c->d = null;
        $a->e = new \stdClass();
        $a->e->f = new \stdClass();
        $a->e->f->g = null;

        $b = [
            null,
            [
                null,
            ],
            [
                3 => null,
            ],
            'x' => [
                new \stdClass(),
            ],
        ];

        $b['x'][0]->y = null;

        return new View(
            name: 'hello_world_set',
            scope: [
                'a' => $a,
                'b' => $b,
            ],
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

    #[Route\Get]
    public function block(): ViewInterface
    {
        return new View(
            name: 'hello_world_block',
        );
    }

    #[Route\Get]
    public function blockTwo(): ViewInterface
    {
        return new View(
            name: 'hello_world_block_two',
        );
    }

    #[Route\Get]
    public function layout(): ViewInterface
    {
        return new View(
            name: 'hello_world_layout',
        );
    }

    #[Route\Get]
    public function exprOne(): ViewInterface
    {
        $this->logger->log('Inside expr_1');

        return new View(
            name: 'hello_world_expr_1',
            scope: [
                'logger' => $this->logger,
                'a' => new \stdClass(),
            ],
        );
    }

    #[Route\Get]
    public function exprTwo(): ViewInterface
    {
        return new View(
            name: 'hello_world_expr_2',
        );
    }

    #[Route\Get]
    public function exprThree(): ViewInterface
    {
        return new View(
            name: 'hello_world_expr_3',
        );
    }

    #[Route\Get]
    public function exprFour(): ViewInterface
    {
        $this->logger->log('Test');

        return new View(
            name: 'hello_world_expr_4',
            scope: [
                'logger' => $this->logger,
            ],
        );
    }

    #[Route\Get]
    public function exprFive(): ViewInterface
    {
        return new View(
            name: 'hello_world_expr_5',
        );
    }

    #[Route\Get]
    public function exprSix(): ViewInterface
    {
        return new View(
            name: 'hello_world_expr_6',
        );
    }

    #[Route\Get]
    public function test(): ViewInterface
    {
        return new View(
            name: 'hello_world_test',
        );
    }
}
