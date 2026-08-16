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

namespace Tuxxedo\Validator\Rule\LanguageCode;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\RuleInterface;
use Tuxxedo\Validator\ValidationContextInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class LanguageCodeRule implements RuleInterface
{
    /**
     * @var list<string>
     */
    private const array CODES = [
        'aa', 'ab', 'ae', 'af', 'ak', 'am', 'an', 'ar', 'as', 'av',
        'ay', 'az', 'ba', 'be', 'bg', 'bh', 'bi', 'bm', 'bn', 'bo',
        'br', 'bs', 'ca', 'ce', 'ch', 'co', 'cr', 'cs', 'cu', 'cv',
        'cy', 'da', 'de', 'dv', 'dz', 'ee', 'el', 'en', 'eo', 'es',
        'et', 'eu', 'fa', 'ff', 'fi', 'fj', 'fo', 'fr', 'fy', 'ga',
        'gd', 'gl', 'gn', 'gu', 'gv', 'ha', 'he', 'hi', 'ho', 'hr',
        'ht', 'hu', 'hy', 'hz', 'ia', 'id', 'ie', 'ig', 'ii', 'ik',
        'io', 'is', 'it', 'iu', 'ja', 'jv', 'ka', 'kg', 'ki', 'kj',
        'kk', 'kl', 'km', 'kn', 'ko', 'kr', 'ks', 'ku', 'kv', 'kw',
        'ky', 'la', 'lb', 'lg', 'li', 'ln', 'lo', 'lt', 'lu', 'lv',
        'mg', 'mh', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'my',
        'na', 'nb', 'nd', 'ne', 'ng', 'nl', 'nn', 'no', 'nr', 'nv',
        'ny', 'oc', 'oj', 'om', 'or', 'os', 'pa', 'pi', 'pl', 'ps',
        'pt', 'qu', 'rm', 'rn', 'ro', 'ru', 'rw', 'sa', 'sc', 'sd',
        'se', 'sg', 'si', 'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr',
        'ss', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'ti',
        'tk', 'tl', 'tn', 'to', 'tr', 'ts', 'tt', 'tw', 'ty', 'ug',
        'uk', 'ur', 'uz', 've', 'vi', 'vo', 'wa', 'wo', 'xh', 'yi',
        'yo', 'za', 'zh', 'zu',
    ];

    public function check(
        mixed $value,
        ValidationContextInterface $context,
        ContainerInterface $container,
    ): ?ViolationInterface {
        if (!\is_string($value)) {
            return new Violation(
                code: CommonViolationCode::WRONG_TYPE,
                propertyPath: $context->currentPath,
                invalidValue: $value,
                context: new WrongTypeViolationContext(
                    expected: 'string',
                    received: \get_debug_type($value),
                ),
            );
        }

        if (!\in_array(\strtolower($value), self::CODES, true)) {
            return new Violation(
                code: LanguageCodeViolationCode::NOT_RECOGNIZED,
                propertyPath: $context->currentPath,
                invalidValue: $value,
            );
        }

        return null;
    }
}
