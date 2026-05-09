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

namespace Support\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\Lumi\Runtime\LoaderInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeInterface;
use Tuxxedo\View\ViewInterface;

class StubRenderer implements LumiViewRenderInterface
{
    /**
     * @var array<array{view: ViewInterface, directives: array<string, string|int|float|bool|null>|null, blocks: array<string, \Closure>|null}>
     */
    public array $renderCalls = [];

    public function __construct(
        public LoaderInterface $loader,
        public RuntimeInterface $runtime,
        public bool $alwaysCompile = false,
        public string $output = '<rendered/>',
    ) {
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
        $this->renderCalls[] = [
            'view' => $view,
            'directives' => $directives,
            'blocks' => $blocks,
        ];

        return $this->output;
    }
}
