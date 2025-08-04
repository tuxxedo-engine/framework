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

namespace Tuxxedo\View\Engine;

use Tuxxedo\View\ViewContextInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

readonly class ViewRender implements ViewRenderInterface
{
    public ViewContextInterface $context;

    public function __construct(
        public string $directory,
        public string $extension = '.phtml',
        ?ViewContextInterface $context = null,
    ) {
        $this->context = $context ?? new ViewContext(
            directory: $this->directory,
            extension: $this->extension,
        );
    }

    public function getViewFileName(
        string $viewName,
    ): string {
        return $this->directory . '/' . \ltrim($viewName, '.') . $this->extension;
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

        $renderer = function (string $viewFileName, array $variables): string {
            \extract($variables, \EXTR_SKIP);
            \ob_start();

            unset($variables);

            require $viewFileName;

            $buffer = \ob_get_clean();

            if ($buffer === false) {
                throw ViewException::fromUnableToCaptureBuffer();
            }

            return $buffer;
        };

        try {
            return $renderer->bindTo($this->context)($this->getViewFileName($view->name), $view->variables);
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        }
    }
}
