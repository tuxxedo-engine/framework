<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Support\View;

use Tuxxedo\View\ViewInterface;
use Tuxxedo\View\ViewRenderInterface;

class RecordingViewRender implements ViewRenderInterface
{
    public ?ViewInterface $lastView = null;

    /**
     * @var array<string, string|int|float|bool|null>|null
     */
    public ?array $lastDirectives = null;

    /**
     * @var array<string, \Closure>|null
     */
    public ?array $lastBlocks = null;

    public function __construct(
        public string $output = '<rendered/>',
    ) {
    }

    public function getViewFileName(
        string $view,
    ): string {
        return $view . '.lumi';
    }

    public function viewExists(
        string $view,
    ): bool {
        return true;
    }

    public function render(
        ViewInterface $view,
        ?array $directives = null,
        ?array $blocks = null,
    ): string {
        $this->lastView = $view;
        $this->lastDirectives = $directives;
        $this->lastBlocks = $blocks;

        return $this->output;
    }
}
