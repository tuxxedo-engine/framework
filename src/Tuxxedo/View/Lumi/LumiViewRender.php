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
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

readonly class LumiViewRender implements LazyInitializableInterface, ViewRenderInterface
{
    /**
     * @var \Closure(string, mixed[]): string
     */
    private \Closure $renderFrame;

    public function __construct(
        public LumiEngine $engine,
        public LoaderInterface $loader,
        public RuntimeInterface $runtime,
        public bool $alwaysCompile = false,
        bool $disableErrorReporting = true,
    ) {
        $this->runtime->renderer($this);

        if ($disableErrorReporting) {
            $this->renderFrame = function (string $__lumiViewFileName, array $__lumiVariables): string {
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
        } else {
            $this->renderFrame = function (string $__lumiViewFileName, array $__lumiVariables): string {
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
        }
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
        ?array $directives = null,
        ?array $blocks = null,
    ): string {
        if (!$this->loader->exists($view->name)) {
            throw ViewException::fromViewNotFound(
                view: $view->name,
            );
        }

        $this->runtime->pushState(
            directives: $directives,
            blocks: $blocks,
        );

        try {
            return $this->renderFrame->bindTo($this->runtime)(
                $this->getCompiledViewFileName($view->name),
                $view->scope,
            );
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        } finally {
            $this->runtime->popState();
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
