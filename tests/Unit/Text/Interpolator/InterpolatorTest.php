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

namespace Unit\Text\Interpolator;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Text\Interpolator\Interpolator;

class InterpolatorTest extends TestCase
{
    public function testInterpolatesPlaceholder(): void
    {
        self::assertSame(
            'Hello Kalle',
            (new Interpolator())->interpolate(
                template: 'Hello {name}',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }

    public function testInterpolatesAdjacentPlaceholders(): void
    {
        self::assertSame(
            'ab',
            (new Interpolator())->interpolate(
                template: '{a}{b}',
                replacements: [
                    'a' => 'a',
                    'b' => 'b',
                ],
            ),
        );
    }

    public function testInterpolatedValuesAreNotInterpolatedAgain(): void
    {
        self::assertSame(
            'path=/u/{id} id=7',
            (new Interpolator())->interpolate(
                template: 'path={path} id={id}',
                replacements: [
                    'path' => '/u/{id}',
                    'id' => '7',
                ],
            ),
        );
    }

    public function testBackslashEscapesLiteralBrace(): void
    {
        self::assertSame(
            'Use {name} to refer to the placeholder',
            (new Interpolator())->interpolate(
                template: 'Use \\{name\\} to refer to the placeholder',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }

    public function testDoubleBackslashRendersSingleBackslash(): void
    {
        self::assertSame(
            'Path is C:\\Users',
            (new Interpolator())->interpolate(
                template: 'Path is C:\\\\Users',
                replacements: [],
            ),
        );
    }

    public function testBackslashBeforeNonSpecialCharStaysLiteral(): void
    {
        self::assertSame(
            'A\\nB',
            (new Interpolator())->interpolate(
                template: 'A\\nB',
                replacements: [],
            ),
        );
    }

    public function testTrailingBackslashStaysLiteral(): void
    {
        self::assertSame(
            'A\\',
            (new Interpolator())->interpolate(
                template: 'A\\',
                replacements: [],
            ),
        );
    }

    public function testUnknownPlaceholderStaysLiteral(): void
    {
        self::assertSame(
            'Hello {name}',
            (new Interpolator())->interpolate(
                template: 'Hello {name}',
                replacements: [],
            ),
        );
    }

    public function testEmptyPlaceholderStaysLiteral(): void
    {
        self::assertSame(
            'Hello {}',
            (new Interpolator())->interpolate(
                template: 'Hello {}',
                replacements: [
                    '' => 'Kalle',
                ],
            ),
        );
    }

    public function testUnclosedBraceStaysLiteral(): void
    {
        self::assertSame(
            'Hello {name',
            (new Interpolator())->interpolate(
                template: 'Hello {name',
                replacements: [
                    'name' => 'Kalle',
                ],
            ),
        );
    }
}
