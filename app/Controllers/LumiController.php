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
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\SymbolInterface;
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

    private function visualizeToken(
        TokenInterface $token,
    ): string {
        $output = $token->type;

        if ($token->op1 !== null || $token->op2 !== null) {
            $output .= ' (';

            if ($token->op1 !== null) {
                $output .= 'op1=' . $this->visualizeValue($token->op1);
            }

            if ($token->op2 !== null) {
                if ($token->op1 !== null) {
                    $output .= ', ';
                }

                $output .= 'op2=' . $this->visualizeValue($token->op2);
            }

            $output .= ')';
        }

        return $output;
    }

    private function visualizeValue(
        mixed $op,
    ): string {
        return \str_replace(["\r\n", "\r", "\n"], '\n', \htmlspecialchars(\var_export($op, true)));
    }

    private function visualizeNode(
        NodeInterface $node,
    ): string {
        $class = new \ReflectionObject($node);
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
        $buffer = $class->getShortName();

        if (\sizeof($properties) > 0) {
            $visualizedProperties = [];

            foreach ($properties as $property) {
                $value = $property->getValue($node);

                $visualizedProperties[] = \sprintf(
                    '%s=%s',
                    $property->getName(),
                    match (true) {
                        $value instanceof NodeInterface => $this->visualizeNode(
                            node: $value,
                        ),
                        $value instanceof \UnitEnum => $this->visualizeEnumValue(
                            value: $value,
                        ),
                        default => $this->visualizeValue($value),
                    },
                );
            }

            $buffer .= '(' . \join(', ', $visualizedProperties) . ')';
        }

        return $buffer;
    }

    private function visualizeEnumValue(
        \UnitEnum $value,
    ): string {
        if ($value instanceof SymbolInterface) {
            return $value->symbol();
        }

        return $value->name;
    }

    #[Route\Get]
    public function index(): ResponseInterface
    {
        $buffer = '<h3>Source</h3>';
        $buffer .= '<pre>';
        $buffer .= \htmlspecialchars($this->viewSource);
        $buffer .= '</pre>';

        $buffer .= '<h3>Tokens</h3>';
        $buffer .= '<pre>';

        $engine = LumiEngine::createDefault();
        $showNext = true;

        try {
            $stream = $engine->lexer->tokenizeByFile($this->viewFile);

            while (!$stream->eof()) {
                $buffer .= $this->visualizeToken($stream->current()) . '<br>';

                $stream->consume();
            }
        } catch (LexerException $exception) {
            $buffer .= $exception;
            $showNext = false;
        }

        $buffer .= '</pre>';

        if ($showNext) {
            $buffer .= '<h3>Nodes</h3>';
            $buffer .= '<pre>';

            try {
                $stream = $engine->parser->parse(
                    stream: $engine->lexer->tokenizeByString(
                        sourceCode: $this->viewSource,
                    ),
                );

                while (!$stream->eof()) {
                    $buffer .= $this->visualizeNode($stream->current()) . '<br>';

                    $stream->consume();
                }
            } catch (ParserException $exception) {
                $buffer .= $exception;
                $showNext = false;
            }

            $buffer .= '</pre>';
        }

        if ($showNext) {
            $buffer .= '<h3>Compiled Source</h3>';
            $buffer .= '<pre>';

            try {
                $buffer .= \htmlspecialchars(
                    $engine->compiler->compile(
                        stream: $engine->parser->parse(
                            stream: $engine->lexer->tokenizeByString(
                                sourceCode: $this->viewSource,
                            ),
                        ),
                    ),
                );
            } catch (CompilerException $exception) {
                $buffer .= $exception;
            }

            $buffer .= '</pre>';
        }

        return Response::html($buffer);
    }
}
