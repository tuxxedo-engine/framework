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

namespace Unit\Validator\Rule\Json;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Validator\RuleTestingTrait;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Rule\Json\JsonRule;
use Tuxxedo\Validator\Rule\Json\JsonViolationCode;
use Tuxxedo\Validator\ViolationCodeInterface;

class JsonRuleTest extends TestCase
{
    use RuleTestingTrait;

    /**
     * @return \Generator<array{0: mixed, 1: (ViolationCodeInterface&\BackedEnum)|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'valid object' => [
            '{"key":"value"}',
            null,
        ];

        yield 'valid array' => [
            '[1,2,3]',
            null,
        ];

        yield 'invalid format' => [
            '{ not json',
            JsonViolationCode::INVALID_FORMAT,
        ];

        yield 'int accepted as json-encodable' => [
            42,
            null,
        ];

        yield 'array accepted as json-encodable' => [
            [
                'a',
                'b',
            ],
            null,
        ];

        yield 'stdClass accepted as json-encodable' => [
            new \stdClass(),
            null,
        ];

        yield 'null skipped' => [
            null,
            null,
        ];

        yield 'unencodable value rejected' => [
            \NAN,
            CommonViolationCode::WRONG_TYPE,
        ];
    }

    #[DataProvider('providesCases')]
    public function testCheck(
        mixed $value,
        ?ViolationCodeInterface $expected,
    ): void {
        $result = $this->runRule(
            rule: new JsonRule(),
            value: $value,
        );

        if ($expected === null) {
            self::assertNull($result);

            return;
        }

        self::assertNotNull($result);
        self::assertSame($expected, $result->code);
    }
}
