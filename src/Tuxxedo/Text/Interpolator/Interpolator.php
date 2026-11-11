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

namespace Tuxxedo\Text\Interpolator;

class Interpolator implements InterpolatorInterface
{
    public function interpolate(
        string $template,
        array $replacements,
    ): string {
        $length = \strlen($template);
        $buffer = '';
        $position = 0;

        while ($position < $length) {
            $char = $template[$position];

            if ($char === '\\' && $position + 1 < $length) {
                $next = $template[$position + 1];

                if ($next === '{' || $next === '}' || $next === '\\') {
                    $buffer .= $next;
                    $position += 2;

                    continue;
                }
            }

            if ($char === '{') {
                $closing = \strpos($template, '}', $position + 1);

                if ($closing !== false) {
                    $name = \substr($template, $position + 1, $closing - $position - 1);

                    if ($name !== '' && \array_key_exists($name, $replacements)) {
                        $buffer .= $replacements[$name];
                        $position = $closing + 1;

                        continue;
                    }
                }
            }

            $buffer .= $char;

            ++$position;
        }

        return $buffer;
    }
}
