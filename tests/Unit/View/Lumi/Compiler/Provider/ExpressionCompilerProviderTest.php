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

namespace Unit\View\Lumi\Compiler\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

// @todo Test compileFunctionCall
// @todo Test compileMethodCall
// @todo Test compileBinaryOp
// @todo Test compileAssignment
// @todo Test compileArray
// @todo Test compileArrayAccess
// @todo Test compileArrayItem
// @todo Test compilePropertyAccess
// @todo Test compileUnaryOp
// @todo Test compileFilterOrBitwiseOr
class ExpressionCompilerProviderTest extends TestCase
{
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new ExpressionCompilerProvider(),
            ],
        );

        $this->compiler->state->enter(NodeScope::STATEMENT);
    }

    public function testCompileIdentifierEmitsLumiVariableLookup(): void
    {
        $output = $this->compiler->compileExpression(
            new IdentifierNode(
                name: 'user',
            ),
        );

        self::assertSame('$__lumiVariables[\'user\']', $output);
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function provideIdentifierNamePreservingVariants(): \Generator
    {
        yield [
            'user_profile_id',
            'user_profile_id',
        ];

        yield [
            'userProfileId',
            'userProfileId',
        ];

        yield [
            'UserProfile',
            'UserProfile',
        ];

        yield [
            'MAX_RETRIES',
            'MAX_RETRIES',
        ];

        yield [
            '_internal',
            '_internal',
        ];

        yield [
            '__hidden',
            '__hidden',
        ];

        yield [
            'column1',
            'column1',
        ];

        yield [
            'x1y2z3',
            'x1y2z3',
        ];

        yield [
            'x',
            'x',
        ];

        yield [
            'naïveCafé',
            'naïveCafé',
        ];

        yield [
            'пользователь',
            'пользователь',
        ];

        yield [
            '用户',
            '用户',
        ];

        yield [
            'user_ñame',
            'user_ñame',
        ];
    }

    #[DataProvider('provideIdentifierNamePreservingVariants')]
    public function testCompileIdentifierPreservesNameVerbatim(
        string $name,
        string $expectedInner,
    ): void {
        $output = $this->compiler->compileExpression(
            new IdentifierNode(
                name: $name,
            ),
        );

        self::assertSame(
            \sprintf('$__lumiVariables[\'%s\']', $expectedInner),
            $output,
        );
    }

    public function testCompileIdentifierEscapesEmbeddedSingleQuoteViaJsEscaper(): void
    {
        $output = $this->compiler->compileExpression(
            new IdentifierNode(
                name: 'weird\'name',
            ),
        );

        self::assertSame('$__lumiVariables[\'weird\\\'name\']', $output);
    }

    public function testCompileIdentifierDoesNotLowercaseName(): void
    {
        $output = $this->compiler->compileExpression(
            new IdentifierNode(
                name: 'MixedCaseName',
            ),
        );

        self::assertSame('$__lumiVariables[\'MixedCaseName\']', $output);
    }

    public function testCompileLiteralStringWrapsInSingleQuotes(): void
    {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: 'hello',
                type: Type::STRING,
            ),
        );

        self::assertSame('\'hello\'', $output);
    }

    public function testCompileLiteralStringEmptyOperandStillWrapped(): void
    {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: '',
                type: Type::STRING,
            ),
        );

        self::assertSame('\'\'', $output);
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function provideStringLiteralEscapingVariants(): \Generator
    {
        yield [
            'it\'s',
            '\'it\\\'s\'',
        ];

        yield [
            'a\'b\'c',
            '\'a\\\'b\\\'c\'',
        ];

        yield [
            '\'wrapped',
            '\'\\\'wrapped\'',
        ];

        yield [
            'wrapped\'',
            '\'wrapped\\\'\'',
        ];

        yield [
            '\'',
            '\'\\\'\'',
        ];

        yield [
            'don\\\'t',
            '\'don\\\\\'t\'',
        ];

        yield [
            'say "hi"',
            '\'say "hi"\'',
        ];

        yield [
            'back\\slash',
            '\'back\\slash\'',
        ];

        yield [
            'café — 日本語',
            '\'café — 日本語\'',
        ];

        yield [
            '   ',
            '\'   \'',
        ];
    }

    #[DataProvider('provideStringLiteralEscapingVariants')]
    public function testCompileLiteralStringEscapesOnlySingleQuotes(
        string $operand,
        string $expected,
    ): void {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: $operand,
                type: Type::STRING,
            ),
        );

        self::assertSame($expected, $output);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function provideIntegerLiteralOperands(): \Generator
    {
        yield [
            '0',
        ];

        yield [
            '7',
        ];

        yield [
            '1234567890',
        ];

        yield [
            '+42',
        ];

        yield [
            '-42',
        ];

        yield [
            '9223372036854775807',
        ];
    }

    #[DataProvider('provideIntegerLiteralOperands')]
    public function testCompileLiteralIntegerEmitsRawOperand(
        string $operand,
    ): void {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: $operand,
                type: Type::INT,
            ),
        );

        self::assertSame($operand, $output);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function provideFloatLiteralOperands(): \Generator
    {
        yield [
            '0.0',
        ];

        yield [
            '3.14',
        ];

        yield [
            '-3.14',
        ];

        yield [
            '+3.14',
        ];

        yield [
            '.5',
        ];

        yield [
            '5.',
        ];

        yield [
            '1.5e3',
        ];

        yield [
            '1.5E3',
        ];

        yield [
            '2.0e-10',
        ];
    }

    #[DataProvider('provideFloatLiteralOperands')]
    public function testCompileLiteralFloatEmitsRawOperand(
        string $operand,
    ): void {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: $operand,
                type: Type::FLOAT,
            ),
        );

        self::assertSame($operand, $output);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function provideBoolLiteralOperands(): \Generator
    {
        yield [
            'true',
        ];

        yield [
            'false',
        ];

        yield [
            'True',
        ];

        yield [
            'False',
        ];

        yield [
            'TRUE',
        ];

        yield [
            'FALSE',
        ];

        yield [
            'tRuE',
        ];
    }

    #[DataProvider('provideBoolLiteralOperands')]
    public function testCompileLiteralBoolEmitsRawOperand(
        string $operand,
    ): void {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: $operand,
                type: Type::BOOL,
            ),
        );

        self::assertSame($operand, $output);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function provideNullLiteralOperands(): \Generator
    {
        yield [
            'null',
        ];

        yield [
            'Null',
        ];

        yield [
            'NULL',
        ];

        yield [
            'nUlL',
        ];
    }

    #[DataProvider('provideNullLiteralOperands')]
    public function testCompileLiteralNullEmitsRawOperand(
        string $operand,
    ): void {
        $output = $this->compiler->compileExpression(
            new LiteralNode(
                operand: $operand,
                type: Type::NULL,
            ),
        );

        self::assertSame($operand, $output);
    }

    public function testCompileLiteralBoolUsesCreateBoolFactory(): void
    {
        $output = $this->compiler->compileExpression(
            LiteralNode::createBool(true),
        );

        self::assertSame('true', $output);
    }

    public function testCompileLiteralNullUsesCreateNullFactory(): void
    {
        $output = $this->compiler->compileExpression(
            LiteralNode::createNull(),
        );

        self::assertSame('null', $output);
    }

    public function testCompileLiteralIntegerUsesCreateIntFactory(): void
    {
        $output = $this->compiler->compileExpression(
            LiteralNode::createInt(42),
        );

        self::assertSame('42', $output);
    }

    public function testCompileLiteralFloatUsesCreateFloatFactory(): void
    {
        $output = $this->compiler->compileExpression(
            LiteralNode::createFloat(3.5),
        );

        self::assertSame('3.5', $output);
    }

    public function testCompileLiteralStringUsesCreateStringFactory(): void
    {
        $output = $this->compiler->compileExpression(
            LiteralNode::createString('it\'s'),
        );

        self::assertSame('\'it\\\'s\'', $output);
    }

    public function testCompileGroupWrapsLiteralIntegerInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new LiteralNode(
                    operand: '5',
                    type: Type::INT,
                ),
            ),
        );

        self::assertSame('(5)', $output);
    }

    public function testCompileGroupWrapsLiteralStringInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new LiteralNode(
                    operand: 'hi',
                    type: Type::STRING,
                ),
            ),
        );

        self::assertSame('(\'hi\')', $output);
    }

    public function testCompileGroupWrapsLiteralBoolInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: LiteralNode::createBool(true),
            ),
        );

        self::assertSame('(true)', $output);
    }

    public function testCompileGroupWrapsLiteralNullInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: LiteralNode::createNull(),
            ),
        );

        self::assertSame('(null)', $output);
    }

    public function testCompileGroupWrapsLiteralFloatInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new LiteralNode(
                    operand: '3.14',
                    type: Type::FLOAT,
                ),
            ),
        );

        self::assertSame('(3.14)', $output);
    }

    public function testCompileGroupWrapsIdentifierInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new IdentifierNode(
                    name: 'user',
                ),
            ),
        );

        self::assertSame('($__lumiVariables[\'user\'])', $output);
    }

    public function testCompileGroupWrapsBinaryOpInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'a',
                    ),
                    right: new IdentifierNode(
                        name: 'b',
                    ),
                    operator: BinarySymbol::ADD,
                ),
            ),
        );

        self::assertSame('($__lumiVariables[\'a\'] + $__lumiVariables[\'b\'])', $output);
    }

    public function testCompileGroupWrapsNestedGroupProducingDoubleParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'x',
                    ),
                ),
            ),
        );

        self::assertSame('(($__lumiVariables[\'x\']))', $output);
    }

    public function testCompileGroupWrapsArrayInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new ArrayNode(
                    items: [
                        new ArrayItemNode(
                            value: new LiteralNode(
                                operand: '1',
                                type: Type::INT,
                            ),
                        ),
                        new ArrayItemNode(
                            value: new LiteralNode(
                                operand: '2',
                                type: Type::INT,
                            ),
                        ),
                    ],
                ),
            ),
        );

        self::assertSame('([1, 2])', $output);
    }

    public function testCompileGroupWrapsArrayAccessInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'items',
                    ),
                    key: new LiteralNode(
                        operand: '0',
                        type: Type::INT,
                    ),
                ),
            ),
        );

        self::assertSame('($__lumiVariables[\'items\'][0])', $output);
    }

    public function testCompileGroupWrapsFunctionCallInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'upper',
                    ),
                    arguments: [
                        new IdentifierNode(
                            name: 'name',
                        ),
                    ],
                ),
            ),
        );

        self::assertSame('($this->functionCall(\'upper\', [$__lumiVariables[\'name\']]))', $output);
    }

    public function testCompileGroupWrapsUnaryPreOperatorInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'count',
                    ),
                    operator: UnarySymbol::NEGATE,
                ),
            ),
        );

        self::assertSame('(-$__lumiVariables[\'count\'])', $output);
    }

    public function testCompileGroupWrapsUnaryPostOperatorInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'count',
                    ),
                    operator: UnarySymbol::INCREMENT_POST,
                ),
            ),
        );

        self::assertSame('($__lumiVariables[\'count\']++)', $output);
    }

    public function testCompileGroupWrapsConcatInParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new GroupNode(
                operand: new ConcatNode(
                    operands: [
                        new LiteralNode(
                            operand: 'a',
                            type: Type::STRING,
                        ),
                        new LiteralNode(
                            operand: 'b',
                            type: Type::STRING,
                        ),
                    ],
                ),
            ),
        );

        self::assertSame('(\'a\' . \'b\')', $output);
    }

    public function testCompileConcatJoinsTwoStringLiteralsWithDot(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new LiteralNode(
                        operand: 'foo',
                        type: Type::STRING,
                    ),
                    new LiteralNode(
                        operand: 'bar',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('\'foo\' . \'bar\'', $output);
    }

    public function testCompileConcatJoinsTwoIdentifiersWithDot(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new IdentifierNode(
                        name: 'first',
                    ),
                    new IdentifierNode(
                        name: 'last',
                    ),
                ],
            ),
        );

        self::assertSame('$__lumiVariables[\'first\'] . $__lumiVariables[\'last\']', $output);
    }

    public function testCompileConcatMixesIdentifierAndLiteral(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new IdentifierNode(
                        name: 'name',
                    ),
                    new LiteralNode(
                        operand: '!',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('$__lumiVariables[\'name\'] . \'!\'', $output);
    }

    public function testCompileConcatJoinsThreeOperands(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new LiteralNode(
                        operand: 'Hello, ',
                        type: Type::STRING,
                    ),
                    new IdentifierNode(
                        name: 'name',
                    ),
                    new LiteralNode(
                        operand: '!',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('\'Hello, \' . $__lumiVariables[\'name\'] . \'!\'', $output);
    }

    public function testCompileConcatJoinsManyOperands(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new LiteralNode(
                        operand: 'a',
                        type: Type::STRING,
                    ),
                    new LiteralNode(
                        operand: 'b',
                        type: Type::STRING,
                    ),
                    new LiteralNode(
                        operand: 'c',
                        type: Type::STRING,
                    ),
                    new LiteralNode(
                        operand: 'd',
                        type: Type::STRING,
                    ),
                    new LiteralNode(
                        operand: 'e',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('\'a\' . \'b\' . \'c\' . \'d\' . \'e\'', $output);
    }

    public function testCompileConcatWithSingleOperandEmitsOperandWithoutSeparator(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new LiteralNode(
                        operand: 'lonely',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('\'lonely\'', $output);
    }

    public function testCompileConcatJoinsMixedLiteralTypes(): void
    {
        /** @var ExpressionNodeInterface[] $operands */
        $operands = [
            LiteralNode::createInt(1),
            LiteralNode::createString('-'),
            LiteralNode::createBool(true),
            LiteralNode::createString('-'),
            LiteralNode::createNull(),
            LiteralNode::createString('-'),
            LiteralNode::createFloat(2.5),
        ];

        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: $operands,
            ),
        );

        self::assertSame('1 . \'-\' . true . \'-\' . null . \'-\' . 2.5', $output);
    }

    public function testCompileConcatWithGroupedOperandsKeepsParentheses(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new GroupNode(
                        operand: new IdentifierNode(
                            name: 'left',
                        ),
                    ),
                    new GroupNode(
                        operand: new IdentifierNode(
                            name: 'right',
                        ),
                    ),
                ],
            ),
        );

        self::assertSame('($__lumiVariables[\'left\']) . ($__lumiVariables[\'right\'])', $output);
    }

    public function testCompileConcatAcceptsNestedConcatAsOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new ConcatNode(
                        operands: [
                            new LiteralNode(
                                operand: 'a',
                                type: Type::STRING,
                            ),
                            new LiteralNode(
                                operand: 'b',
                                type: Type::STRING,
                            ),
                        ],
                    ),
                    new LiteralNode(
                        operand: 'c',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('\'a\' . \'b\' . \'c\'', $output);
    }

    public function testCompileConcatWithFunctionCallOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new FunctionCallNode(
                        name: new IdentifierNode(
                            name: 'upper',
                        ),
                        arguments: [
                            new IdentifierNode(
                                name: 'name',
                            ),
                        ],
                    ),
                    new LiteralNode(
                        operand: '!',
                        type: Type::STRING,
                    ),
                ],
            ),
        );

        self::assertSame('$this->functionCall(\'upper\', [$__lumiVariables[\'name\']]) . \'!\'', $output);
    }

    public function testCompileConcatWithArrayAccessOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new ConcatNode(
                operands: [
                    new ArrayAccessNode(
                        array: new IdentifierNode(
                            name: 'parts',
                        ),
                        key: new LiteralNode(
                            operand: '0',
                            type: Type::INT,
                        ),
                    ),
                    new ArrayAccessNode(
                        array: new IdentifierNode(
                            name: 'parts',
                        ),
                        key: new LiteralNode(
                            operand: '1',
                            type: Type::INT,
                        ),
                    ),
                ],
            ),
        );

        self::assertSame('$__lumiVariables[\'parts\'][0] . $__lumiVariables[\'parts\'][1]', $output);
    }
}
