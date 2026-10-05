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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class EscapeFilters
{
    public function __construct(
        private readonly EscaperInterface $escaper = new Escaper(),
    ) {
    }

    #[LumiFilter('escape_html', aliases: ['e', 'escape'])]
    public function escapeHtml(
        mixed $value,
    ): mixed {
        if (!\is_string($value)) {
            return $value;
        }

        return $this->escaper->html($value);
    }

    #[LumiFilter('escape_attr')]
    public function escapeAttr(
        mixed $value,
    ): mixed {
        if (!\is_string($value)) {
            return $value;
        }

        return $this->escaper->attribute($value);
    }

    #[LumiFilter('escape_css')]
    public function escapeCss(
        mixed $value,
    ): mixed {
        if (!\is_string($value)) {
            return $value;
        }

        return $this->escaper->css($value);
    }

    #[LumiFilter('escape_js')]
    public function escapeJs(
        mixed $value,
    ): mixed {
        if (!\is_string($value)) {
            return $value;
        }

        return $this->escaper->js($value);
    }

    #[LumiFilter('escape_html_comment')]
    public function escapeHtmlComment(
        mixed $value,
    ): mixed {
        if (!\is_string($value)) {
            return $value;
        }

        return $this->escaper->htmlComment($value);
    }
}
