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

use Tuxxedo\Collection\CollectionInterface;
use Tuxxedo\Collection\FileCollection;
use Tuxxedo\Container\Resolver\ConfigValue;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\LumiViewRender;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\SymbolInterface;
use Tuxxedo\View\Lumi\Token\TokenInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;

#[Controller(uri: '/lumi/')]
readonly class LumiController
{
    public function __construct(
        #[ConfigValue('view.directory')] private string $viewDirectory,
        #[ConfigValue('view.cacheDirectory')] private string $viewCacheDirectory,
        private LumiViewRender $lumiViewRender,
        private ViewController $viewController,
    ) {
    }

    private function visualizeToken(
        TokenInterface $token,
    ): string {
        $output = \sprintf(
            'L%s %s',
            \str_pad((string) $token->line, 3),
            $token->type,
        );

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
        mixed $value,
    ): string {
        \ob_start();
        \var_dump($value);

        /** @var string $value */
        $value = \ob_get_clean();

        /** @var list<string> $lines */
        $lines = \preg_split('/\n/', $value);
        $value = [];

        foreach ($lines as $line) {
            $value[] = \mb_trim($line);
        }

        return \mb_trim(\htmlspecialchars(\join('\n', $value)), '\n');
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

    private function visualizeViewFileName(
        string $viewFile,
    ): string {
        $viewFile = $this->getShortViewName($viewFile);
        $compiledViewFile = $this->viewCacheDirectory . '/' . \str_replace('.lumi', '.php', $viewFile);
        $buffer = '<strong>' . $viewFile . '</strong>';

        if (\is_file($compiledViewFile)) {
            $buffer .= ' (compiled)';
        }

        $buffer .= '<br>';

        return $buffer;
    }

    /**
     * @return CollectionInterface<array-key, string>
     */
    private function getViewFiles(): CollectionInterface
    {
        return FileCollection::fromDirectory($this->viewDirectory)
            ->filter(\is_file(...));
    }

    private function getShortViewName(
        string $viewFile,
        bool $extension = true,
    ): string {
        $file = \str_replace($this->viewDirectory . '/', '', $viewFile);

        if (!$extension) {
            return \str_replace('.lumi', '', $file);
        }

        return $file;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws HttpException
     */
    private function getTestScopeFor(
        string $name,
    ): array {
        $view = (match ($name) {
            'hello_world' => $this->viewController->hello(...),
            'hello_world_call' => $this->viewController->call(...),
            'hello_world_cond' => $this->viewController->cond(...),
            'hello_world_declare' => $this->viewController->decl(...),
            'hello_world_for' => $this->viewController->for(...),
            'hello_world_include' => $this->viewController->include(...),
            'hello_world_method' => $this->viewController->method(...),
            'hello_world_set' => $this->viewController->set(...),
            'hello_world_while' => $this->viewController->while(...),
            default => throw HttpException::fromInternalServerError(),
        })();

        if (!$view instanceof View) {
            throw HttpException::fromInternalServerError();
        }

        return $view->scope;
    }

    #[Route\Get]
    public function index(RequestInterface $request): ResponseInterface
    {
        if ($request->get->has('file')) {
            $selectedViewFile = $this->viewDirectory . '/' . $request->get->getString('file');
        } else {
            $selectedViewFile = $this->viewDirectory . '/hello_world_include.lumi';
        }

        $viewSource = @\file_get_contents($selectedViewFile);

        if ($viewSource === false) {
            throw HttpException::fromInternalServerError();
        }

        $buffer = '<h3>File</h3>';
        $buffer .= '<form>';
        $buffer .= '<select onchange="window.location = \'?file=\' + this.options[this.selectedIndex].value;">';

        foreach ($this->getViewFiles() as $viewFile) {
            $viewFile = $this->getShortViewName($viewFile);
            $selected = $this->getShortViewName($selectedViewFile) === $viewFile
                ? ' selected'
                : '';

            $buffer .= '<option' . $selected . ' value="' . $viewFile . '">' . $viewFile . '</option>';
        }

        $buffer .= '</select>';
        $buffer .= '</form>';

        $buffer .= '<h3>Source</h3>';
        $buffer .= $this->visualizeViewFileName($selectedViewFile);
        $buffer .= '<pre>';
        $buffer .= \htmlspecialchars($viewSource);
        $buffer .= '</pre>';

        $buffer .= '<h3>Tokens</h3>';
        $buffer .= '<pre>';

        $engine = LumiEngine::createDefault();
        $showNext = true;

        try {
            $stream = $engine->lexer->tokenizeByString($viewSource);

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
                        sourceCode: $viewSource,
                    ),
                );

                while (!$stream->eof()) {
                    $buffer .= $this->visualizeNode($stream->current()) . '<br>';

                    $stream->consume();
                }
            } catch (LexerException|ParserException $exception) {
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
                                sourceCode: $viewSource,
                            ),
                        ),
                    ),
                );
            } catch (CompilerException $exception) {
                $buffer .= $exception;
                $showNext = false;
            }

            $buffer .= '</pre>';
        }

        if ($showNext) {
            $viewName = $this->getShortViewName(
                viewFile: $selectedViewFile,
                extension: false,
            );

            $viewScope = $this->getTestScopeFor(
                name: $viewName,
            );

            if (\sizeof($viewScope) > 0) {
                $buffer .= '<h3>Injected scope</h3>';
                $buffer .= '<pre>';

                foreach ($viewScope as $variable => $value) {
                    $buffer .= '$' . $variable . ' = ' . \get_debug_type($value) . "\n";
                }

                $buffer .= '</pre>';
            }

            $buffer .= '<h3>Output</h3>';

            try {
                $buffer .= $this->lumiViewRender->render(
                    view: new View(
                        name: $viewName,
                        scope: $viewScope,
                    ),
                );
            } catch (ViewException $exception) {
                $buffer .= '<pre>';
                $buffer .= $exception;
                $buffer .= '</pre>';
            }
        }

        return Response::html($buffer);
    }
}
