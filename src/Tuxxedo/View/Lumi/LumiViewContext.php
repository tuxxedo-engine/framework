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

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewContextInterface;
use Tuxxedo\View\ViewRenderInterface;

class LumiViewContext implements ViewContextInterface
{
    public readonly EscaperInterface $escaper;
    public private(set) array $directives;

    public function __construct(
        public readonly ViewRenderInterface $render,
        ?EscaperInterface $escaper = null,
    ) {
        $this->escaper = $escaper ?? new Escaper();

        $this->resetDirectives();
    }

    // @todo Improve this to a better API with whitelisting or local override like include()
    public function functionCall(
        string $functionName,
        array $arguments = [],
    ): mixed {
        // @todo Remove hard coding
        if (\strval($functionName) === 'include') {
            if (\sizeof($arguments) === 0) {
                throw new \Exception('Too few arguments');
            } elseif (\sizeof($arguments) > 2) {
                throw new \Exception('Too many arguments');
            }

            $viewName = $arguments[0];
            $scope = [];

            if (\sizeof($arguments) > 1) {
                if (!\is_array($arguments[1])) {
                    throw new \Exception('Scope must be an array');
                }

                /** @var array<string, mixed> $scope */
                $scope = $arguments[1];
            }

            if (!\is_string($viewName)) {
                throw new \Exception('Argument must be string');
            }

            return $this->include($viewName, $scope);
        }

        return $functionName(...$arguments);
    }

    public function resetDirectives(): void
    {
        $this->directives = [
            'lumi.autoescape' => true,
        ];
    }

    // @todo Improve this to a better API with handlers
    public function directive(
        string $directive,
        string|int|float|bool|null $value,
    ): void {
        $this->directives[$directive] = $value;
    }

    public function include(
        string $viewName,
        array $scope = [],
    ): string {
        return $this->render->render(
            new View(
                name: $viewName,
                scope: $scope,
            ),
        );
    }

    public function escapeHtml(
        string $input,
    ): string {
        return $this->escaper->html($input);
    }

    public function escapeAttribute(
        string $input,
    ): string {
        return $this->escaper->attribute($input);
    }

    public function escapeJs(
        string $input,
    ): string {
        return $this->escaper->js($input);
    }

    public function escapeUrl(
        string $input,
    ): string {
        return $this->escaper->url($input);
    }
}
