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

namespace Unit\View\Lumi\Library\Directive;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Directive\MutableDirectives;

class MutableDirectivesTest extends TestCase
{
    public function testSetAddsNewDirective(): void
    {
        $directives = new MutableDirectives([]);
        $directives->set('foo', 'bar');

        self::assertTrue($directives->has('foo'));
    }

    public function testSetOverwritesExistingDirective(): void
    {
        $directives = new MutableDirectives(
            directives: [
                'foo' => 'old',
            ],
        );

        $directives->set('foo', 'new');

        self::assertSame('new', $directives->asString('foo'));
    }

    public function testCreateWithDefaultsReturnsInstance(): void
    {
        self::assertInstanceOf(MutableDirectives::class, MutableDirectives::createWithDefaults());
    }

    public function testCreateWithDefaultsHasAutoescapeDirective(): void
    {
        self::assertTrue(MutableDirectives::createWithDefaults()->has('lumi.autoescape'));
    }

    public function testCreateWithDefaultsHasStripCommentsDirective(): void
    {
        self::assertTrue(MutableDirectives::createWithDefaults()->has('lumi.strip_comments'));
    }
}
