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

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\Token\TokenInterface;

#[Controller(uri: '/lumi/')]
readonly class LumiController
{
    private string $viewFile;
    private string $viewSource;

    public function __construct()
    {
        $this->viewFile = __DIR__ . '/../views/lumi/hello_world_set.lumi';
        $viewSource = @\file_get_contents($this->viewFile);

        if ($viewSource === false) {
            throw HttpException::fromInternalServerError();
        }

        $this->viewSource = $viewSource;
    }

    #[Route\Get]
    public function hello(): ResponseInterface
    {
        $buffer = '<h3>Source</h3>';
        $buffer .= '<pre>' . \htmlspecialchars($this->viewSource) . '</pre>';
        $buffer .= '<h3>Compiled Source</h3>';
        $buffer .= '<pre>' . \htmlspecialchars(LumiEngine::createDefault()->compileFile($this->viewFile)->source) . '</pre>';

        return Response::html($buffer);
    }

    private function visualizeToken(TokenInterface $token): string
    {
        $output = $token->type;

        if ($token->op1 !== null || $token->op2 !== null) {
            $output .= ' (';

            if ($token->op1 !== null) {
                $output .= 'op1=' . $this->visualizeOpValue($token->op1);
            }

            if ($token->op2 !== null) {
                if ($token->op1 !== null) {
                    $output .= ', ';
                }

                $output .= 'op2=' . $this->visualizeOpValue($token->op2);
            }

            $output .= ')';
        }

        return $output;
    }

    private function visualizeOpValue(string $op): string
    {
        return \str_replace(["\r\n", "\r", "\n"], '\n', \htmlspecialchars(\var_export($op, true)));
    }

    #[Route\Get]
    public function token(): ResponseInterface
    {
        $buffer = '<h3>Source</h3>';
        $buffer .= '<pre>';
        $buffer .= \htmlspecialchars($this->viewSource);
        $buffer .= '</pre>';

        $buffer .= '<h3>Tokens</h3>';
        $buffer .= '<pre>';

        try {
            $stream = LumiEngine::createDefaultLexer()->tokenizeByFile($this->viewFile);

            while (!$stream->eof()) {
                $buffer .= $this->visualizeToken($stream->current()) . '<br>';

                $stream->consume();
            }
        } catch (LexerException $exception) {
            $buffer .= $exception;
        }

        $buffer .= '</pre>';

        return Response::html($buffer);
    }
}
