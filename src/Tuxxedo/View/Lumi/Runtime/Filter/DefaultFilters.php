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

namespace Tuxxedo\View\Lumi\Runtime\Filter;

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Runtime\DirectivesInterface;

readonly class DefaultFilters implements FilterProviderInterface
{
    private readonly EscaperInterface $escaper;

    public function __construct(
        ?EscaperInterface $escaper = null
    ) {
        $this->escaper = $escaper ?? new Escaper();
    }

    private function upperImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_strtoupper($value);
    }

    private function lowerImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_strtolower($value);
    }

    private function trimImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_trim($value);
    }

    private function ltrimImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_ltrim($value);
    }

    private function rtrimImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_rtrim($value);
    }

    private function capitalizeImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_convert_case($value, \MB_CASE_TITLE);
    }

    private function lcfirstImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_lcfirst($value);
    }

    private function stripTagsImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \strip_tags($value);
    }

    private function nl2brImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \nl2br($value);
    }

    private function slugifyImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return \mb_trim(
            \preg_replace(
                '/[^\p{L}\p{Nd}]+/u',
                '-',
                \mb_strtolower(
                    $value,
                ),
            ) ?? '',
        );
    }

    private function escapeHtmlImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        if ($directives->asBool('lumi.autoescape')) {
            return $value;
        }

        return $this->escaper->html($value);
    }

    private function escapeAttrImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        if ($directives->asBool('lumi.autoescape')) {
            return $value;
        }

        return $this->escaper->attribute($value);
    }

    private function escapeJsImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        if ($directives->asBool('lumi.autoescape')) {
            return $value;
        }

        return $this->escaper->js($value);
    }

    private function lengthImplementation(
        mixed $value,
        DirectivesInterface $directives,
    ): int {
        /** @var array<mixed> $value */

        return \sizeof($value);
    }

    public function export(): \Generator
    {
        yield [
            'upper',
            $this->upperImplementation(...),
        ];

        yield [
            'lower',
            $this->lowerImplementation(...),
        ];

        yield [
            'trim',
            $this->trimImplementation(...),
        ];

        yield [
            'ltrim',
            $this->ltrimImplementation(...),
        ];

        yield [
            'rtrim',
            $this->rtrimImplementation(...),
        ];

        yield [
            'capitalize',
            $this->capitalizeImplementation(...),
        ];

        yield [
            'lcfirst',
            $this->lcfirstImplementation(...),
        ];

        yield [
            'strip_tags',
            $this->stripTagsImplementation(...),
        ];

        yield [
            'nl2br',
            $this->nl2brImplementation(...),
        ];

        yield [
            'slugify',
            $this->slugifyImplementation(...),
        ];

        yield [
            'escape_html',
            $this->escapeHtmlImplementation(...),
        ];

        yield [
            'escape_attr',
            $this->escapeAttrImplementation(...),
        ];

        yield [
            'escape_js',
            $this->escapeJsImplementation(...),
        ];

        yield [
            'escape',
            $this->escapeHtmlImplementation(...),
        ];

        yield [
            'e',
            $this->escapeHtmlImplementation(...),
        ];

        yield [
            'length',
            $this->lengthImplementation(...),
        ];
    }
}
