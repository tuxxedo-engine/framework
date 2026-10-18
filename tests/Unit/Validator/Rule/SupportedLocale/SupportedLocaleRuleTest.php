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

namespace Unit\Validator\Rule\SupportedLocale;

use Fixture\Intl\InMemoryPhraseLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\PluralSelector;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;
use Tuxxedo\Intl\Translator;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\SupportedLocale\SupportedLocaleRule;
use Tuxxedo\Validator\Rule\SupportedLocale\SupportedLocaleViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class SupportedLocaleRuleTest extends TestCase
{
    use RuleTestingTrait;

    private function container(): ContainerInterface
    {
        $config = new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
            supportedLocales: [
                Locale::parse('cs-CZ'),
            ],
        );

        $container = new Container();

        $container->singleton(
            new Translator(
                config: $config,
                loader: new InMemoryPhraseLoader(
                    catalogues: [],
                ),
                resolver: new StaticLocaleResolver(
                    config: $config,
                ),
                pluralSelector: new PluralSelector(),
            ),
        );

        return $container;
    }

    /**
     * @return \Generator<string, array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'default locale' => [
            'en-US',
            null,
        ];

        yield 'configured locale' => [
            'cs-CZ',
            null,
        ];

        yield 'configured locale, lower case' => [
            'cs_cz',
            null,
        ];

        yield 'locale object' => [
            Locale::parse('cs-CZ'),
            null,
        ];

        yield 'valid but unsupported' => [
            'de-DE',
            SupportedLocaleViolationCode::NOT_SUPPORTED,
        ];

        yield 'language only of a supported locale' => [
            'cs',
            SupportedLocaleViolationCode::NOT_SUPPORTED,
        ];

        yield 'not a tag' => [
            'not a tag',
            SupportedLocaleViolationCode::NOT_SUPPORTED,
        ];

        yield 'wrong type' => [
            42,
            CommonViolationCode::WRONG_TYPE,
        ];

        yield 'null skipped' => [
            null,
            null,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ViolationCodeInterface|null $expectedCode,
    ): void {
        $result = $this->runRule(
            rule: new SupportedLocaleRule(),
            container: $this->container(),
            value: $value,
        );

        if ($expectedCode === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expectedCode, $result->code);
    }
}
