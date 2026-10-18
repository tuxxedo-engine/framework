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

namespace Tuxxedo\Intl;

enum Normalize: string
{
    case NONE = 'none';
    case CANONICAL = 'canonical';
    case COMPATIBLE = 'compatible';

    public function apply(
        string $value,
    ): ?string {
        $form = match ($this) {
            self::NONE => null,
            self::CANONICAL => \Normalizer::FORM_C,
            self::COMPATIBLE => \Normalizer::FORM_KC,
        };

        if ($form === null) {
            return $value;
        }

        $normalized = \Normalizer::normalize($value, $form);

        return \is_string($normalized)
            ? $normalized
            : null;
    }
}
