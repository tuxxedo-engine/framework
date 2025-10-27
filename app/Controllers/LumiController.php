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
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Highlight\Theme\LumiDark;
use Tuxxedo\View\Lumi\Syntax\Highlight\Theme\LumiLight;
use Tuxxedo\View\Lumi\Syntax\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\SymbolInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewRenderInterface;

#[Controller(uri: '/lumi/')]
readonly class LumiController
{
    public function __construct(
        #[ConfigValue('view.directory')] private string $viewDirectory,
        #[ConfigValue('view.cacheDirectory')] private string $viewCacheDirectory,
        private ViewRenderInterface $lumiViewRender,
        private ViewController $viewController,
    ) {
    }

    private function visualizeTokenHeader(): string
    {
        return \sprintf(
            "%s %s %s %s %s\n",
            \str_pad('Line', 6),
            \str_pad('Position', 9),
            \str_pad('Token', 20),
            \str_pad('op1', 25),
            \str_pad('op2', 25),
        );
    }

    private function visualizeToken(
        TokenStreamInterface $stream,
    ): string {
        $token = $stream->current();

        return \sprintf(
            'L%s @%s %s %s %s',
            \str_pad((string) $token->line, 5),
            \str_pad((string) $stream->position, 8),
            \str_pad($token->type, 20),
            $token->op1 !== null
                ? $this->visualizeTokenValue($token->op1)
                : \str_repeat(' ', 25),
            $token->op2 !== null
                ? $this->visualizeTokenValue($token->op2)
                : \str_repeat(' ', 25),
        );
    }

    private function visualizeTokenValue(
        string $value,
    ): string {
        if (\mb_strlen($value) > 20) {
            $value = \mb_substr($value, 0, 20) . '...';
        }

        /** @var list<string> $lines */
        $lines = \preg_split('/\n/', $value);
        $value = [];

        foreach ($lines as $line) {
            $value[] = \mb_trim($line);
        }

        return \mb_str_pad('"' . \htmlspecialchars(\join('\n', $value)) . '"', 25);
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
            ->filter(\is_file(...))
            ->filter(
                static fn (string $name): bool => \str_contains($name, 'hello_world_'),
            );
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
            'hello_world_block' => $this->viewController->block(...),
            'hello_world_block_two' => $this->viewController->blockTwo(...),
            'hello_world_call' => $this->viewController->call(...),
            'hello_world_cond' => $this->viewController->cond(...),
            'hello_world_dce' => $this->viewController->dce(...),
            'hello_world_declare' => $this->viewController->decl(...),
            'hello_world_expr_1' => $this->viewController->exprOne(...),
            'hello_world_expr_2' => $this->viewController->exprTwo(...),
            'hello_world_expr_3' => $this->viewController->exprThree(...),
            'hello_world_expr_4' => $this->viewController->exprFour(...),
            'hello_world_expr_5' => $this->viewController->exprFive(...),
            'hello_world_expr_6' => $this->viewController->exprSix(...),
            'hello_world_for' => $this->viewController->for(...),
            'hello_world_include' => $this->viewController->include(...),
            'hello_world_layout' => $this->viewController->layout(...),
            'hello_world_method' => $this->viewController->method(...),
            'hello_world_seq' => $this->viewController->seq(...),
            'hello_world_set' => $this->viewController->set(...),
            'hello_world_test' => $this->viewController->test(...),
            'hello_world_while' => $this->viewController->while(...),
            default => throw HttpException::fromInternalServerError(),
        })();

        if (!$view instanceof View) {
            throw HttpException::fromInternalServerError();
        }

        return $view->scope;
    }

    private function visualizeSccpPass(
        string &$buffer,
        NodeStreamInterface $nodeStream,
    ): NodeStreamInterface {
        $pass = 1;
        $optimizer = new SccpOptimizer();
        $stream = $nodeStream;

        do {
            $buffer .= \sprintf(
                '<h3>Nodes (SCCP pass %d)</h3>',
                $pass++,
            );

            $buffer .= '<pre>';

            $result = $optimizer->optimize($stream);
            $stream = $result->stream;
            $copy = clone $stream;

            while (!$copy->eof()) {
                $buffer .= $this->visualizeNode($copy->current()) . '<br>';

                $copy->consume();
            }

            $buffer .= '</pre>';
        } while ($result->changed);

        return $result->stream;
    }

    private function visualizeDcePass(
        string &$buffer,
        NodeStreamInterface $nodeStream,
    ): NodeStreamInterface {
        $pass = 1;
        $optimizer = new DceOptimizer();
        $stream = $nodeStream;

        do {
            $buffer .= \sprintf(
                '<h3>Nodes (DCE pass %d)</h3>',
                $pass++,
            );

            $buffer .= '<pre>';

            $result = $optimizer->optimize($stream);
            $stream = $result->stream;
            $copy = clone $stream;

            while (!$copy->eof()) {
                $buffer .= $this->visualizeNode($copy->current()) . '<br>';

                $copy->consume();
            }

            $buffer .= '</pre>';
        } while ($result->changed);

        return $result->stream;
    }

    private function getHighlightThemeClass(
        string $theme,
    ): ?ThemeInterface {
        return match (true) {
            $theme === 'dark' => new LumiDark(),
            $theme === 'light' => new LumiLight(),
            default => null,
        };
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
        $selectedHighlightTheme = $request->get->getString('highlight');

        if ($viewSource === false) {
            throw HttpException::fromInternalServerError();
        }

        $buffer = '<form>';
        $buffer .= '<script>';
        $buffer .= 'function formCheck() {';
        $buffer .= 'var sccp = 0;';
        $buffer .= 'var dce = 0;';
        $buffer .= 'var highlight = \'0\';';
        $buffer .= 'const $ = (id) => document.getElementById(id);';
        $buffer .= 'if ($(\'sccp\').checked){ sccp = 1; }';
        $buffer .= 'if ($(\'dce\').checked){ dce = 1; }';
        $buffer .= 'if ($(\'highlightTheme\').selectedIndex) { highlight = $(\'highlightTheme\').options[$(\'highlightTheme\').selectedIndex].value; }';
        $buffer .= 'window.location = \'?file=\' + $(\'file\').options[$(\'file\').selectedIndex].value + \'&sccp=\' + sccp + \'&dce=\' + dce + \'&highlight=\' + highlight;';
        $buffer .= '}';
        $buffer .= '</script>';
        $buffer .= '<select id="file" onchange="formCheck();">';

        // @todo Set a default $selectedViewFile if not contained in collection
        foreach ($this->getViewFiles() as $viewFile) {
            $viewFile = $this->getShortViewName($viewFile);
            $selected = $this->getShortViewName($selectedViewFile) === $viewFile
                ? ' selected'
                : '';

            $buffer .= '<option' . $selected . ' value="' . $viewFile . '">' . $viewFile . '</option>';
        }

        $buffer .= '</select>';
        $buffer .= '<select id="highlightTheme" onchange="formCheck();">';
        $buffer .= '<option></option>';
        $buffer .= '<option value="dark"' . ($selectedHighlightTheme === 'dark' ? ' selected' : '') . '>Dark</option>';
        $buffer .= '<option value="light"' . ($selectedHighlightTheme === 'light' ? ' selected' : '') . '>Light</option>';
        $buffer .= '</select>';
        $buffer .= '<p>';
        $buffer .= '<input type="checkbox" id="sccp"' . ($request->get->getBool('sccp') ? ' checked' : '') . ' onclick="formCheck();">';
        $buffer .= '<label for="sccp">SCCP Optimizer</label>';
        $buffer .= '</p>';
        $buffer .= '<p>';
        $buffer .= '<input type="checkbox" id="dce"' . ($request->get->getBool('dce') ? ' checked' : '') . ' onclick="formCheck();">';
        $buffer .= '<label for="dce">DCE Optimizer</label>';
        $buffer .= '</p>';
        $buffer .= '</form>';

        $buffer .= '<h3>Source</h3>';
        $buffer .= $this->visualizeViewFileName($selectedViewFile);
        $buffer .= '<pre>';
        $buffer .= \htmlspecialchars($viewSource);
        $buffer .= '</pre>';

        $buffer .= '<h3>Tokens</h3>';
        $buffer .= '<pre>';
        $buffer .= $this->visualizeTokenHeader();

        $optimizers = [];

        if ($request->get->getBool('sccp')) {
            $optimizers[] = new SccpOptimizer();
        }

        if ($request->get->getBool('dce')) {
            $optimizers[] = new DceOptimizer();
        }

        $engine = LumiEngine::createCustom(
            optimizers: $optimizers,
        );

        try {
            $tokenStream = $engine->lexer->tokenizeByString($viewSource);

            while (!$tokenStream->eof()) {
                $buffer .= $this->visualizeToken($tokenStream) . '<br>';

                $tokenStream->consume();
            }
        } catch (LumiException $exception) {
            $buffer .= $exception;
        }

        $buffer .= '</pre>';

        if (isset($tokenStream) && !isset($exception)) {
            $buffer .= '<h3>Nodes (pre-optimized)</h3>';
            $buffer .= '<pre>';

            try {
                $nodeStream = $engine->parser->parse(
                    stream: clone $tokenStream,
                );

                while (!$nodeStream->eof()) {
                    $buffer .= $this->visualizeNode($nodeStream->current()) . '<br>';

                    $nodeStream->consume();
                }

                $nodeStream = clone $nodeStream;
            } catch (LumiException $exception) {
                $buffer .= $exception;
            }

            $buffer .= '</pre>';
        }

        if (isset($nodeStream) && !isset($exception)) {
            if ($request->get->getBool('sccp')) {
                $nodeStream = $this->visualizeSccpPass($buffer, $nodeStream);
            }

            if ($request->get->getBool('dce')) {
                $nodeStream = $this->visualizeDcePass($buffer, $nodeStream);
            }

            $buffer .= '<h3>Compiled Source</h3>';
            $buffer .= '<pre>';

            try {
                $buffer .= \htmlspecialchars(
                    $engine->compiler->compile(
                        stream: $nodeStream,
                    ),
                );
            } catch (LumiException $exception) {
                $buffer .= $exception;
            }

            $buffer .= '</pre>';
        }

        $theme = $this->getHighlightThemeClass($selectedHighlightTheme);

        if ($theme !== null && !isset($exception)) {
            $buffer .= '<h3>Highlighted source code</h3>';
            $buffer .= '<div style="' . ($selectedHighlightTheme === 'dark' ? 'background: #0D1117; ' : '') . 'padding: 12px;">';
            $buffer .= '<pre style="margin: 0px;">';

            try {
                $buffer .= $engine->highlightFile(
                    file: $selectedViewFile,
                    theme: $theme,
                );
            } catch (LumiException $exception) {
                $buffer .= $exception;
            }

            $buffer .= '</pre>';
            $buffer .= '</div>';
        } elseif (!isset($exception)) {
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
