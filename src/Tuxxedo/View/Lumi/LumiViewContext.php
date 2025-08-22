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

readonly class LumiViewContext implements ViewContextInterface
{
    public EscaperInterface $escaper;

    public function __construct(
        public ViewRenderInterface $render,
        ?EscaperInterface $escaper = null,
    ) {
        $this->escaper = $escaper ?? new Escaper();
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
