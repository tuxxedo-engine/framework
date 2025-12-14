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

namespace Tuxxedo\View\Lumi\Highlight\Theme;

use Tuxxedo\View\Lumi\Highlight\HighlightException;

class ThemeFactory implements ThemeFactoryInterface
{
    /**
     * @param ThemeInterface[] $themes
     */
    final public function __construct(
        public private(set) array $themes = [],
    ) {
    }

    /**
     * @return ThemeInterface[]
     */
    public static function createDefaultThemes(): array
    {
        return [
            new LumiLight(),
            new LumiDark(),
        ];
    }

    /**
     * @param ThemeInterface[] $themes
     */
    public static function createDefault(
        array $themes = [],
    ): static {
        return new static(
            themes: \array_merge(
                $themes,
                self::createDefaultThemes(),
            ),
        );
    }

    public function find(
        string $identifier,
    ): ThemeInterface {
        foreach ($this->themes as $theme) {
            if (\strcasecmp($theme->identifier, $identifier) === 0) {
                return $theme;
            }
        }

        throw HighlightException::fromInvalidTheme(
            theme: $identifier,
        );
    }
}
