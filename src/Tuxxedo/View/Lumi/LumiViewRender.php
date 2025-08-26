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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\Container\Resolver\ConfigValue;
use Tuxxedo\View\ViewContextInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

readonly class LumiViewRender implements ViewRenderInterface
{
    public ViewContextInterface $context;
    public LumiEngine $lumi;

    public function __construct(
        #[ConfigValue('view.directory')] public string $directory,
        #[ConfigValue('view.cacheDirectory')] public string $cacheDirectory,
        #[ConfigValue('view.alwaysCompile')] public bool $alwaysCompile,
        ?LumiEngine $lumi = null,
    ) {
        $this->context = new LumiViewContext(
            render: $this,
        );

        $this->lumi = $lumi ?? LumiEngine::createDefault();
    }

    public function getViewFileName(
        string $viewName,
    ): string {
        return $this->directory . '/' . \ltrim($viewName, '.') . '.lumi';
    }

    public function viewExists(
        string $viewName,
    ): bool {
        return \is_file($this->getViewFileName($viewName));
    }

    public function render(
        ViewInterface $view,
    ): string {
        if (!$this->viewExists($view->name)) {
            throw ViewException::fromViewNotFound(
                viewName: $view->name,
            );
        }

        $this->context->resetDirectives();

        $renderer = function (string $__lumiViewFileName, array $__lumiVariables): string {
            \extract($__lumiVariables, \EXTR_SKIP);
            \ob_start();

            unset($__lumiVariables);

            require $__lumiViewFileName;

            $buffer = \ob_get_clean();

            if ($buffer === false) {
                throw ViewException::fromUnableToCaptureBuffer();
            }

            return $buffer;
        };

        try {
            return $renderer->bindTo($this->context)($this->getCompiledViewFile($view->name), $view->scope);
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        }
    }

    private function getCompiledViewFileName(
        string $viewName,
    ): string {
        return $this->cacheDirectory . '/' . \ltrim($viewName, '.') . '.php';
    }

    private function getCompiledViewFile(
        string $viewName,
    ): string {
        $compiledViewFile = $this->getCompiledViewFileName($viewName);

        // @todo Implement proper caching
        if (!$this->alwaysCompile && \is_file($compiledViewFile)) {
            return $compiledViewFile;
        }

        $this->lumi->compileFile($this->getViewFileName($viewName))->save($compiledViewFile);

        return $compiledViewFile;
    }
}
