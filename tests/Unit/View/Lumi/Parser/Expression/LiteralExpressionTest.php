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

namespace Unit\View\Lumi\Parser\Expression;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Syntax\Type;

class LiteralExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesSingleQuotedString(): void
    {
        $node = $this->helper->parse('\'hello\'');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'hello',
            expectedType: Type::STRING,
        );
    }

    public function testParsesDoubleQuotedString(): void
    {
        $node = $this->helper->parse('"hello"');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'hello',
            expectedType: Type::STRING,
        );
    }

    public function testParsesEmptyString(): void
    {
        $node = $this->helper->parse('\'\'');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '',
            expectedType: Type::STRING,
        );
    }

    public function testParsesStringWithInteriorWhitespace(): void
    {
        $node = $this->helper->parse('\'hello world\'');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'hello world',
            expectedType: Type::STRING,
        );
    }

    public function testParsesPositiveInteger(): void
    {
        $node = $this->helper->parse('42');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '42',
            expectedType: Type::INT,
        );
    }

    public function testParsesNegativeIntegerAsSingleLiteral(): void
    {
        $node = $this->helper->parse('-42');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '-42',
            expectedType: Type::INT,
        );
    }

    public function testParsesZero(): void
    {
        $node = $this->helper->parse('0');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '0',
            expectedType: Type::INT,
        );
    }

    public function testParsesMultiDigitInteger(): void
    {
        $node = $this->helper->parse('12345');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '12345',
            expectedType: Type::INT,
        );
    }

    public function testParsesSimpleFloat(): void
    {
        $node = $this->helper->parse('3.14');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '3.14',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesFloatWithLeadingDot(): void
    {
        $node = $this->helper->parse('.5');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '.5',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesFloatWithTrailingDot(): void
    {
        $node = $this->helper->parse('1.');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1.',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesNegativeFloat(): void
    {
        $node = $this->helper->parse('-3.14');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '-3.14',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesScientificNotationFloatWithoutDecimalPoint(): void
    {
        $node = $this->helper->parse('1e5');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1e5',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesScientificNotationFloatWithDecimalPoint(): void
    {
        $node = $this->helper->parse('1.0e5');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1.0e5',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesDecimalScientificNotationFloat(): void
    {
        $node = $this->helper->parse('1.5e2');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1.5e2',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesScientificNotationFloatWithNegativeExponent(): void
    {
        $node = $this->helper->parse('1e-5');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1e-5',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesDecimalScientificNotationFloatWithNegativeExponent(): void
    {
        $node = $this->helper->parse('1.5e-2');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1.5e-2',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesUppercaseScientificNotationFloat(): void
    {
        $node = $this->helper->parse('1.5E2');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: '1.5E2',
            expectedType: Type::FLOAT,
        );
    }

    public function testParsesTrueLiteral(): void
    {
        $node = $this->helper->parse('true');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'true',
            expectedType: Type::BOOL,
        );
    }

    public function testParsesFalseLiteral(): void
    {
        $node = $this->helper->parse('false');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'false',
            expectedType: Type::BOOL,
        );
    }

    public function testRecognizesUppercaseTrueAsBoolPreservingCase(): void
    {
        $node = $this->helper->parse('TRUE');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'TRUE',
            expectedType: Type::BOOL,
        );
    }

    public function testRecognizesMixedCaseFalseAsBoolPreservingCase(): void
    {
        $node = $this->helper->parse('False');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'False',
            expectedType: Type::BOOL,
        );
    }

    public function testParsesNullLiteral(): void
    {
        $node = $this->helper->parse('null');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'null',
            expectedType: Type::NULL,
        );
    }

    public function testRecognizesUppercaseNullPreservingCase(): void
    {
        $node = $this->helper->parse('NULL');

        $this->assertLiteralNode(
            node: $node,
            expectedOperand: 'NULL',
            expectedType: Type::NULL,
        );
    }
}
