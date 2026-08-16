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

namespace Unit\Validator\Message;

use Fixture\Validator\FixtureBackedEnumContext;
use Fixture\Validator\FixtureMixedTypesContext;
use Fixture\Validator\FixtureReceivedContext;
use Fixture\Validator\FixtureUnitEnum;
use Fixture\Validator\FixtureUnitEnumContext;
use Fixture\Validator\FixtureViolationCode;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Validator\Message\DefaultEnglishMessageFormatter;
use Tuxxedo\Validator\Violation;

class DefaultEnglishMessageFormatterTest extends TestCase
{
    public function testFormatFallsBackWhenNoTemplateBound(): void
    {
        $formatter = new DefaultEnglishMessageFormatter();

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'field',
                invalidValue: 'x',
            ),
        );

        self::assertStringContainsString('field', $formatted);
        self::assertStringContainsString('fixture.always-fail', $formatted);
    }

    public function testFormatInterpolatesTemplateWithPathAndValue(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => 'Field {path} rejected value {value}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'email',
                invalidValue: 'not-an-email',
            ),
        );

        self::assertSame('Field email rejected value not-an-email', $formatted);
    }

    public function testFormatInterpolatesCustomContextPlaceholders(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.odd-number' => 'Got odd number {received} at {path}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ODD_NUMBER,
                propertyPath: 'age',
                invalidValue: 7,
                context: new FixtureReceivedContext(
                    received: 7,
                ),
            ),
        );

        self::assertSame('Got odd number 7 at age', $formatted);
    }

    public function testFormatStringifiesNullBoolAndArrayContextValues(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => '{nullish}|{flag}|{list}|{obj}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'x',
                invalidValue: null,
                context: new FixtureMixedTypesContext(
                    nullish: null,
                    flag: true,
                    list: [
                        'a',
                        'b',
                    ],
                    obj: new \stdClass(),
                ),
            ),
        );

        self::assertSame('null|true|a, b|stdClass', $formatted);
    }

    public function testFormatStringifiesBackedEnumUsingItsValue(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => '{status}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'x',
                invalidValue: null,
                context: new FixtureBackedEnumContext(
                    status: FixtureViolationCode::ALWAYS_FAIL,
                ),
            ),
        );

        self::assertSame('fixture.always-fail', $formatted);
    }

    public function testFormatStringifiesUnitEnumUsingItsName(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => '{tag}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'x',
                invalidValue: null,
                context: new FixtureUnitEnumContext(
                    tag: FixtureUnitEnum::ALPHA,
                ),
            ),
        );

        self::assertSame('ALPHA', $formatted);
    }

    public function testFormatEmitsNullForNullInvalidValueInDefaultTemplate(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => '{value}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'x',
                invalidValue: null,
            ),
        );

        self::assertSame('null', $formatted);
    }

    public function testFormatEmitsFalseForFalseInvalidValue(): void
    {
        $formatter = new DefaultEnglishMessageFormatter(
            templates: [
                'fixture.always-fail' => '{value}',
            ],
        );

        $formatted = $formatter->format(
            violation: new Violation(
                code: FixtureViolationCode::ALWAYS_FAIL,
                propertyPath: 'x',
                invalidValue: false,
            ),
        );

        self::assertSame('false', $formatted);
    }
}
