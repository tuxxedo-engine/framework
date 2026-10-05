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

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class StringFilters
{
    #[LumiFilter('capitalize')]
    public function capitalize(
        string $value,
    ): string {
        return \mb_convert_case($value, \MB_CASE_TITLE);
    }

    #[LumiFilter('lcfirst')]
    public function lcfirst(
        string $value,
    ): string {
        return \mb_lcfirst($value);
    }

    #[LumiFilter('lower')]
    public function lower(
        string $value,
    ): string {
        return \mb_strtolower($value);
    }

    #[LumiFilter('upper')]
    public function upper(
        string $value,
    ): string {
        return \mb_strtoupper($value);
    }

    #[LumiFilter('ltrim')]
    public function ltrim(
        string $value,
    ): string {
        return \mb_ltrim($value);
    }

    #[LumiFilter('rtrim')]
    public function rtrim(
        string $value,
    ): string {
        return \mb_rtrim($value);
    }

    #[LumiFilter('trim')]
    public function trim(
        string $value,
    ): string {
        return \mb_trim($value);
    }

    #[LumiFilter('slugify')]
    public function slugify(
        string $value,
    ): string {
        return \mb_trim(
            \preg_replace(
                '/[^\p{L}\p{Nd}]+/u',
                '-',
                \mb_strtolower($value),
            ) ?? '',
        );
    }

    #[LumiFilter('strip_tags')]
    public function stripTags(
        string $value,
    ): string {
        return \strip_tags($value);
    }

    #[LumiFilter('nl2br')]
    public function nl2br(
        string $value,
    ): string {
        return \nl2br($value, false);
    }
}
