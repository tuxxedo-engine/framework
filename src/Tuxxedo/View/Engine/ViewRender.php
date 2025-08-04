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

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\LazyInitializableInterface;
use Tuxxedo\View\ViewContextInterface;
use Tuxxedo\View\ViewException;
use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

readonly class ViewRender implements LazyInitializableInterface, ViewRenderInterface
{
    public ViewContextInterface $context;

    public function __construct(
        public string $directory,
        public string $extension,
    ) {
        $this->context = new ViewContext($this);
    }

    public static function createInstance(
        ContainerInterface $container,
    ): self {
        $config = $container->resolve(ConfigInterface::class);

        return new self(
            directory: $config->getString('views.directory'),
            extension: $config->getString('views.extension'),
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
            return $renderer->bindTo($this->context)($this->getViewFileName($view->name), $view->scope);
        } catch (\Throwable $exception) {
            throw ViewException::fromViewRenderException(
                exception: $exception,
            );
        }
    }
}
