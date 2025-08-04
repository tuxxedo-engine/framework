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
        $this->context = $context ?? new ViewContext();
    }

    public function render(
        ViewInterface $view,
    ): string {
        $file = $this->getViewFileName($view->name);

        if (!$this->viewExists($file)) {
            throw ViewException::fromViewNotFound(
                viewName: $view->name,
            );
        }

        return $this->isolatedRender(
            fileName: $file,
            variables: $view->variables,
        );
    }

    private function getViewFileName(
        string $viewName,
    ): string {
        return $this->directory . '/' . \ltrim($viewName, '.') . $this->extension;
    }

    private function viewExists(
        string $fileName,
    ): bool {
        return \is_file($fileName);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws ViewException
     */
    private function isolatedRender(
        string $fileName,
        array $variables,
    ): string {
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
            return $renderer->bindTo($this->context)($fileName, $variables);
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        }
    }
}
