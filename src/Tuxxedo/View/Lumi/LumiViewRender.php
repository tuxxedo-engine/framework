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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\LazyInitializableInterface;
use Tuxxedo\View\Lumi\Runtime\LumiLoaderInterface;
use Tuxxedo\View\Lumi\Runtime\LumiRuntimeInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

readonly class LumiViewRender implements LazyInitializableInterface, ViewRenderInterface
{
    public function __construct(
        public LumiEngine $engine,
        public LumiLoaderInterface $loader,
        public LumiRuntimeInterface $runtime,
        public bool $alwaysCompile,
    ) {
    }

    public static function createInstance(
        ContainerInterface $container,
    ): self {
        /** @var LumiViewRender */
        return LumiConfigurator::fromConfig($container)->build();
    }

    public function getViewFileName(
        string $view,
    ): string {
        return $this->loader->getViewFileName($view);
    }

    public function viewExists(
        string $view,
    ): bool {
        return $this->loader->exists($view);
    }

    public function render(
        ViewInterface $view,
    ): string {
        if (!$this->loader->exists($view->name)) {
            throw ViewException::fromViewNotFound(
                view: $view->name,
            );
        }

        $this->runtime->resetDirectives();

        // @todo This needs to trap notices and warnings to not leak information
        $renderer = function (string $__lumiViewFileName, array $__lumiVariables): string {
            \extract($__lumiVariables, \EXTR_SKIP);
            \ob_start();

            unset($__lumiVariables);

            $__lumiErrorReporting = \error_reporting(0);
            require $__lumiViewFileName;
            \error_reporting($__lumiErrorReporting);

            $buffer = \ob_get_clean();

            if ($buffer === false) {
                throw ViewException::fromUnableToCaptureBuffer();
            }

            return $buffer;
        };

        try {
            return $renderer->bindTo($this->runtime)($this->getCompiledViewFileName($view->name), $view->scope);
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        }
    }

    private function getCompiledViewFileName(
        string $view,
    ): string {
        $viewFileName = $this->loader->getViewFileName($view);
        $compiledFileName = $this->loader->getCachedFileName($view);

        if ($this->alwaysCompile || !$this->loader->isCached($view)) {
            $this->engine->compileFile($viewFileName)->save($compiledFileName);
        }

        return $compiledFileName;
    }
}
