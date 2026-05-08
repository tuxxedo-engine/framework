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
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\CompilerStateFlag;
use Tuxxedo\View\Lumi\Compiler\Provider\ExpressionCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

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

    public function testCompileBinaryOpEmitsLeftOperatorRightSeparatedBySpaces(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new IdentifierNode(
                    name: 'b',
                ),
                operator: BinarySymbol::ADD,
            ),
        );

        self::assertSame('$__lumiVariables[\'a\'] + $__lumiVariables[\'b\']', $output);
    }

    /**
     * @return \Generator<array{0: BinarySymbol, 1: string}>
     */
    public static function provideBinaryOperatorVariants(): \Generator
    {
        yield [
            BinarySymbol::CONCAT,
            '.',
        ];

        yield [
            BinarySymbol::ADD,
            '+',
        ];

        yield [
            BinarySymbol::SUBTRACT,
            '-',
        ];

        yield [
            BinarySymbol::MULTIPLY,
            '*',
        ];

        yield [
            BinarySymbol::DIVIDE,
            '/',
        ];

        yield [
            BinarySymbol::MODULUS,
            '%',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_IMPLICIT,
            '===',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_EXPLICIT,
            '===',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT,
            '!==',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
            '!==',
        ];

        yield [
            BinarySymbol::GREATER,
            '>',
        ];

        yield [
            BinarySymbol::LESS,
            '<',
        ];

        yield [
            BinarySymbol::GREATER_EQUAL,
            '>=',
        ];

        yield [
            BinarySymbol::LESS_EQUAL,
            '<=',
        ];

        yield [
            BinarySymbol::AND,
            '&&',
        ];

        yield [
            BinarySymbol::OR,
            '||',
        ];

        yield [
            BinarySymbol::XOR,
            'xor',
        ];

        yield [
            BinarySymbol::EXPONENTIATE,
            '**',
        ];

        yield [
            BinarySymbol::BITWISE_AND,
            '&',
        ];

        yield [
            BinarySymbol::BITWISE_OR,
            '|',
        ];

        yield [
            BinarySymbol::BITWISE_XOR,
            '^',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_LEFT,
            '<<',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_RIGHT,
            '>>',
        ];

        yield [
            BinarySymbol::NULL_COALESCE,
            '??',
        ];

        yield [
            BinarySymbol::NULL_SAFE_ACCESS,
            '?.',
        ];
    }

    #[DataProvider('provideBinaryOperatorVariants')]
    public function testCompileBinaryOpEmitsCompiledOperatorBetweenIdentifierOperands(
        BinarySymbol $operator,
        string $expectedOperator,
    ): void {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new IdentifierNode(
                    name: 'b',
                ),
                operator: $operator,
            ),
        );

        self::assertSame(
            \sprintf('$__lumiVariables[\'a\'] %s $__lumiVariables[\'b\']', $expectedOperator),
            $output,
        );
    }

    public function testCompileBinaryOpAcceptsLiteralOperands(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: LiteralNode::createInt(2),
                right: LiteralNode::createInt(3),
                operator: BinarySymbol::MULTIPLY,
            ),
        );

        self::assertSame('2 * 3', $output);
    }

    public function testCompileBinaryOpComposesNestedBinaryOps(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'a',
                    ),
                    right: new IdentifierNode(
                        name: 'b',
                    ),
                    operator: BinarySymbol::ADD,
                ),
                right: new IdentifierNode(
                    name: 'c',
                ),
                operator: BinarySymbol::MULTIPLY,
            ),
        );

        self::assertSame('$__lumiVariables[\'a\'] + $__lumiVariables[\'b\'] * $__lumiVariables[\'c\']', $output);
    }

    public function testCompileBinaryOpNullSafeAccessClearsFlagAfterCompilation(): void
    {
        $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new IdentifierNode(
                    name: 'b',
                ),
                operator: BinarySymbol::NULL_SAFE_ACCESS,
            ),
        );

        self::assertFalse(
            $this->compiler->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testCompileBinaryOpNullCoalesceClearsFlagAfterCompilation(): void
    {
        $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new IdentifierNode(
                    name: 'b',
                ),
                operator: BinarySymbol::NULL_COALESCE,
            ),
        );

        self::assertFalse(
            $this->compiler->state->hasFlag(CompilerStateFlag::NULL_SAFE_ACCESS),
        );
    }

    public function testCompileBinaryOpNullSafeAccessFlagPropagatesToPropertyAccessOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'b',
                    ),
                    property: 'name',
                ),
                operator: BinarySymbol::NULL_SAFE_ACCESS,
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'a\'] ?. $this->propertyAccess($__lumiVariables[\'b\'], true)?->name',
            $output,
        );
    }

    public function testCompileBinaryOpNullCoalesceFlagPropagatesToPropertyAccessOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'b',
                    ),
                    property: 'name',
                ),
                operator: BinarySymbol::NULL_COALESCE,
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'a\'] ?? $this->propertyAccess($__lumiVariables[\'b\'], true)?->name',
            $output,
        );
    }

    public function testCompileBinaryOpNonNullSafeOperatorDoesNotMarkPropertyAccessAsNullSafe(): void
    {
        $output = $this->compiler->compileExpression(
            new BinaryOpNode(
                left: new IdentifierNode(
                    name: 'a',
                ),
                right: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'b',
                    ),
                    property: 'name',
                ),
                operator: BinarySymbol::ADD,
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'a\'] + $this->propertyAccess($__lumiVariables[\'b\'])->name',
            $output,
        );
    }

    public function testCompileUnaryOpPreEmitsOperatorThenOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'count',
                ),
                operator: UnarySymbol::NEGATE,
            ),
        );

        self::assertSame('-$__lumiVariables[\'count\']', $output);
    }

    public function testCompileUnaryOpPostEmitsOperandThenOperator(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'count',
                ),
                operator: UnarySymbol::INCREMENT_POST,
            ),
        );

        self::assertSame('$__lumiVariables[\'count\']++', $output);
    }

    /**
     * @return \Generator<array{0: UnarySymbol, 1: string}>
     */
    public static function provideUnaryOperatorVariants(): \Generator
    {
        yield [
            UnarySymbol::NOT,
            '!$__lumiVariables[\'x\']',
        ];

        yield [
            UnarySymbol::NEGATE,
            '-$__lumiVariables[\'x\']',
        ];

        yield [
            UnarySymbol::BITWISE_NOT,
            '~$__lumiVariables[\'x\']',
        ];

        yield [
            UnarySymbol::INCREMENT_PRE,
            '++$__lumiVariables[\'x\']',
        ];

        yield [
            UnarySymbol::INCREMENT_POST,
            '$__lumiVariables[\'x\']++',
        ];

        yield [
            UnarySymbol::DECREMENT_PRE,
            '--$__lumiVariables[\'x\']',
        ];

        yield [
            UnarySymbol::DECREMENT_POST,
            '$__lumiVariables[\'x\']--',
        ];
    }

    #[DataProvider('provideUnaryOperatorVariants')]
    public function testCompileUnaryOpAppliesOperatorToIdentifierOperand(
        UnarySymbol $operator,
        string $expected,
    ): void {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'x',
                ),
                operator: $operator,
            ),
        );

        self::assertSame($expected, $output);
    }

    public function testCompileUnaryOpAppliesPreOperatorToLiteralOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: LiteralNode::createInt(5),
                operator: UnarySymbol::NEGATE,
            ),
        );

        self::assertSame('-5', $output);
    }

    public function testCompileUnaryOpAppliesPreOperatorToBoolLiteral(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: LiteralNode::createBool(true),
                operator: UnarySymbol::NOT,
            ),
        );

        self::assertSame('!true', $output);
    }

    public function testCompileUnaryOpAppliesOperatorToGroupedOperand(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new GroupNode(
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
                operator: UnarySymbol::NEGATE,
            ),
        );

        self::assertSame('-($__lumiVariables[\'a\'] + $__lumiVariables[\'b\'])', $output);
    }

    public function testCompileUnaryOpComposesNestedUnaryOps(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'flag',
                    ),
                    operator: UnarySymbol::NOT,
                ),
                operator: UnarySymbol::NOT,
            ),
        );

        self::assertSame('!!$__lumiVariables[\'flag\']', $output);
    }

    public function testCompileUnaryOpComposesPreAndPost(): void
    {
        $output = $this->compiler->compileExpression(
            new UnaryOpNode(
                operand: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'count',
                    ),
                    operator: UnarySymbol::INCREMENT_POST,
                ),
                operator: UnarySymbol::NEGATE,
            ),
        );

        self::assertSame('-$__lumiVariables[\'count\']++', $output);
    }

    private function compileNode(
        AssignmentNode $node,
    ): string {
        return $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );
    }

    public function testCompileAssignmentToIdentifierEmitsLumiVariableAssign(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'x\'] = 1; ?>', $output);
    }

    /**
     * @return \Generator<array{0: AssignmentSymbol, 1: string}>
     */
    public static function provideAssignmentOperatorVariants(): \Generator
    {
        yield [
            AssignmentSymbol::ASSIGN,
            '=',
        ];

        yield [
            AssignmentSymbol::CONCAT,
            '.=',
        ];

        yield [
            AssignmentSymbol::NULL_ASSIGN,
            '??=',
        ];

        yield [
            AssignmentSymbol::ADD,
            '+=',
        ];

        yield [
            AssignmentSymbol::SUBTRACT,
            '-=',
        ];

        yield [
            AssignmentSymbol::MULTIPLY,
            '*=',
        ];

        yield [
            AssignmentSymbol::DIVIDE,
            '/=',
        ];

        yield [
            AssignmentSymbol::MODULUS,
            '%=',
        ];

        yield [
            AssignmentSymbol::EXPONENTIATE,
            '**=',
        ];

        yield [
            AssignmentSymbol::BITWISE_AND,
            '&=',
        ];

        yield [
            AssignmentSymbol::BITWISE_OR,
            '|=',
        ];

        yield [
            AssignmentSymbol::BITWISE_XOR,
            '^=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_LEFT,
            '<<=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_RIGHT,
            '>>=',
        ];
    }

    #[DataProvider('provideAssignmentOperatorVariants')]
    public function testCompileAssignmentToIdentifierEmitsCompiledOperator(
        AssignmentSymbol $operator,
        string $expectedOperator,
    ): void {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(1),
                operator: $operator,
            ),
        );

        self::assertSame(
            \sprintf('<?php $__lumiVariables[\'x\'] %s 1; ?>', $expectedOperator),
            $output,
        );
    }

    public function testCompileAssignmentToIdentifierEscapesNameViaJsEscaper(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'weird\'name',
                ),
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'weird\\\'name\'] = 1; ?>', $output);
    }

    public function testCompileAssignmentToPropertyAccessEmitsArrowAccess(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'user',
                    ),
                    property: 'name',
                ),
                value: LiteralNode::createString('Kalle'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'user\']->name = \'Kalle\'; ?>', $output);
    }

    #[DataProvider('provideAssignmentOperatorVariants')]
    public function testCompileAssignmentToPropertyAccessSupportsAllOperators(
        AssignmentSymbol $operator,
        string $expectedOperator,
    ): void {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'user',
                    ),
                    property: 'count',
                ),
                value: LiteralNode::createInt(1),
                operator: $operator,
            ),
        );

        self::assertSame(
            \sprintf('<?php $__lumiVariables[\'user\']->count %s 1; ?>', $expectedOperator),
            $output,
        );
    }

    public function testCompileAssignmentToNestedPropertyAccessUnwrapsAllAccessors(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new PropertyAccessNode(
                        accessor: new IdentifierNode(
                            name: 'user',
                        ),
                        property: 'profile',
                    ),
                    property: 'name',
                ),
                value: LiteralNode::createString('Kalle'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'user\']->profile->name = \'Kalle\'; ?>',
            $output,
        );
    }

    public function testCompileAssignmentToPropertyAccessRestoresStatementScope(): void
    {
        $this->compileNode(
            new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'user',
                    ),
                    property: 'name',
                ),
                value: LiteralNode::createString('Kalle'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            NodeScope::STATEMENT,
            $this->compiler->state->expects,
        );
    }

    public function testCompileAssignmentToArrayAccessWithKeyEmitsBracketAccess(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'items',
                    ),
                    key: LiteralNode::createInt(0),
                ),
                value: LiteralNode::createString('first'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'items\'][0] = \'first\'; ?>', $output);
    }

    #[DataProvider('provideAssignmentOperatorVariants')]
    public function testCompileAssignmentToArrayAccessSupportsAllOperators(
        AssignmentSymbol $operator,
        string $expectedOperator,
    ): void {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'items',
                    ),
                    key: LiteralNode::createInt(0),
                ),
                value: LiteralNode::createInt(1),
                operator: $operator,
            ),
        );

        self::assertSame(
            \sprintf('<?php $__lumiVariables[\'items\'][0] %s 1; ?>', $expectedOperator),
            $output,
        );
    }

    public function testCompileAssignmentToArrayAccessWithStringKey(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'config',
                    ),
                    key: LiteralNode::createString('locale'),
                ),
                value: LiteralNode::createString('en'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'config\'][\'locale\'] = \'en\'; ?>',
            $output,
        );
    }

    public function testCompileAssignmentToArrayAccessWithoutKeyEmitsEmptyBrackets(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'items',
                    ),
                ),
                value: LiteralNode::createString('appended'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'items\'][] = \'appended\'; ?>', $output);
    }

    public function testCompileAssignmentToArrayAccessUsesIdentifierKey(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'items',
                    ),
                    key: new IdentifierNode(
                        name: 'index',
                    ),
                ),
                value: LiteralNode::createInt(1),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'items\'][$__lumiVariables[\'index\']] = 1; ?>',
            $output,
        );
    }

    public function testCompileAssignmentValueCanBeIdentifier(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'target',
                ),
                value: new IdentifierNode(
                    name: 'source',
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'target\'] = $__lumiVariables[\'source\']; ?>',
            $output,
        );
    }

    public function testCompileAssignmentValueCanBeBinaryOp(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'sum',
                ),
                value: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'a',
                    ),
                    right: new IdentifierNode(
                        name: 'b',
                    ),
                    operator: BinarySymbol::ADD,
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'sum\'] = $__lumiVariables[\'a\'] + $__lumiVariables[\'b\']; ?>',
            $output,
        );
    }

    public function testCompileAssignmentValueCanBeFunctionCall(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'name',
                ),
                value: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'upper',
                    ),
                    arguments: [
                        new IdentifierNode(
                            name: 'raw',
                        ),
                    ],
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'name\'] = $this->functionCall(\'upper\', [$__lumiVariables[\'raw\']]); ?>',
            $output,
        );
    }

    public function testCompileAssignmentValueCanBeArray(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'items',
                ),
                value: new ArrayNode(
                    items: [
                        new ArrayItemNode(
                            value: LiteralNode::createInt(1),
                        ),
                        new ArrayItemNode(
                            value: LiteralNode::createInt(2),
                        ),
                    ],
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame('<?php $__lumiVariables[\'items\'] = [1, 2]; ?>', $output);
    }

    public function testCompileAssignmentValueCanBeGroup(): void
    {
        $output = $this->compileNode(
            new AssignmentNode(
                name: new IdentifierNode(
                    name: 'wrapped',
                ),
                value: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'inner',
                    ),
                ),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );

        self::assertSame(
            '<?php $__lumiVariables[\'wrapped\'] = ($__lumiVariables[\'inner\']); ?>',
            $output,
        );
    }

    public function testCompileMethodCallEmitsInstanceCallWithoutArguments(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'logout',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'])->logout()',
            $output,
        );
    }

    public function testCompileMethodCallEmitsInstanceCallWithSingleArgument(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'rename',
                arguments: [
                    LiteralNode::createString('Kalle'),
                ],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'])->rename(\'Kalle\')',
            $output,
        );
    }

    public function testCompileMethodCallEmitsInstanceCallWithMultipleArguments(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'set',
                arguments: [
                    LiteralNode::createString('locale'),
                    LiteralNode::createString('en'),
                    LiteralNode::createBool(true),
                ],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'])->set(\'locale\', \'en\', true)',
            $output,
        );
    }

    public function testCompileMethodCallSupportsMixedArgumentTypes(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'service',
                ),
                name: 'invoke',
                arguments: [
                    LiteralNode::createInt(1),
                    LiteralNode::createFloat(2.5),
                    LiteralNode::createNull(),
                    new IdentifierNode(
                        name: 'flag',
                    ),
                    new ArrayNode(
                        items: [
                            new ArrayItemNode(
                                value: LiteralNode::createInt(0),
                            ),
                        ],
                    ),
                ],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'service\'])->invoke(1, 2.5, null, $__lumiVariables[\'flag\'], [0])',
            $output,
        );
    }

    public function testCompileMethodCallPreservesMethodNameVerbatim(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'GetFullName',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'])->GetFullName()',
            $output,
        );
    }

    public function testCompileMethodCallChainingProducesNestedInstanceCalls(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'user',
                    ),
                    name: 'profile',
                    arguments: [],
                ),
                name: 'avatar',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($this->instanceCall($__lumiVariables[\'user\'])->profile())->avatar()',
            $output,
        );
    }

    public function testCompileMethodCallOnPropertyAccessCaller(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'user',
                    ),
                    property: 'profile',
                ),
                name: 'render',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($this->propertyAccess($__lumiVariables[\'user\'])->profile)->render()',
            $output,
        );
    }

    public function testCompileMethodCallOnArrayAccessCaller(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'services',
                    ),
                    key: LiteralNode::createString('db'),
                ),
                name: 'connect',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'services\'][\'db\'])->connect()',
            $output,
        );
    }

    public function testCompileMethodCallOnFunctionCallCaller(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'service',
                    ),
                    arguments: [
                        LiteralNode::createString('db'),
                    ],
                ),
                name: 'connect',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($this->functionCall(\'service\', [\'db\']))->connect()',
            $output,
        );
    }

    public function testCompileMethodCallOnGroupedCaller(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'user',
                    ),
                ),
                name: 'tap',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall(($__lumiVariables[\'user\']))->tap()',
            $output,
        );
    }

    public function testCompileMethodCallNullSafeOnNodeEmitsNullSafeForm(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'render',
                arguments: [],
                nullSafe: true,
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'], true)?->render()',
            $output,
        );
    }

    public function testCompileMethodCallNullSafeOnStateFlagEmitsNullSafeForm(): void
    {
        $this->compiler->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);

        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'render',
                arguments: [],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'], true)?->render()',
            $output,
        );
    }

    public function testCompileMethodCallNullSafeFromBothFlagAndNodeEmitsNullSafeFormOnce(): void
    {
        $this->compiler->state->flag(CompilerStateFlag::NULL_SAFE_ACCESS);

        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'render',
                arguments: [],
                nullSafe: true,
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'], true)?->render()',
            $output,
        );
    }

    public function testCompileMethodCallNullSafeOnNodeWithArguments(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'rename',
                arguments: [
                    LiteralNode::createString('Kalle'),
                    LiteralNode::createBool(false),
                ],
                nullSafe: true,
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'], true)?->rename(\'Kalle\', false)',
            $output,
        );
    }

    public function testCompileMethodCallArgumentsCanBeIdentifiers(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'user',
                ),
                name: 'merge',
                arguments: [
                    new IdentifierNode(
                        name: 'a',
                    ),
                    new IdentifierNode(
                        name: 'b',
                    ),
                ],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'user\'])->merge($__lumiVariables[\'a\'], $__lumiVariables[\'b\'])',
            $output,
        );
    }

    public function testCompileMethodCallArgumentCanBeMethodCall(): void
    {
        $output = $this->compiler->compileExpression(
            new MethodCallNode(
                caller: new IdentifierNode(
                    name: 'a',
                ),
                name: 'with',
                arguments: [
                    new MethodCallNode(
                        caller: new IdentifierNode(
                            name: 'b',
                        ),
                        name: 'value',
                        arguments: [],
                    ),
                ],
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'a\'])->with($this->instanceCall($__lumiVariables[\'b\'])->value())',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithIdentifierRightEmitsFilterTernary(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'name',
                ),
                right: new IdentifierNode(
                    name: 'upper',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'upper\') ? $this->filter($__lumiVariables[\'name\'], \'upper\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'upper\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrLowercasesFilterName(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'name',
                ),
                right: new IdentifierNode(
                    name: 'UPPER',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'upper\') ? $this->filter($__lumiVariables[\'name\'], \'upper\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'UPPER\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrLowercasesMixedCaseFilterName(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'name',
                ),
                right: new IdentifierNode(
                    name: 'TitleCase',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'titlecase\') ? $this->filter($__lumiVariables[\'name\'], \'titlecase\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'TitleCase\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrEscapesSingleQuoteInFilterName(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'name',
                ),
                right: new IdentifierNode(
                    name: 'weird\'filter',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'weird\\\'filter\') ? $this->filter($__lumiVariables[\'name\'], \'weird\\\'filter\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'weird\\\'filter\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrSupportsLiteralLeft(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: LiteralNode::createString('hello'),
                right: new IdentifierNode(
                    name: 'upper',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'upper\') ? $this->filter(\'hello\', \'upper\') : ((\'hello\') | ($__lumiVariables[\'upper\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrSupportsBinaryOpLeft(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'a',
                    ),
                    right: new IdentifierNode(
                        name: 'b',
                    ),
                    operator: BinarySymbol::ADD,
                ),
                right: new IdentifierNode(
                    name: 'upper',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'upper\') ? $this->filter($__lumiVariables[\'a\'] + $__lumiVariables[\'b\'], \'upper\') : (($__lumiVariables[\'a\'] + $__lumiVariables[\'b\']) | ($__lumiVariables[\'upper\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrSupportsMethodCallLeft(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'user',
                    ),
                    name: 'name',
                    arguments: [],
                ),
                right: new IdentifierNode(
                    name: 'upper',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'upper\') ? $this->filter($this->instanceCall($__lumiVariables[\'user\'])->name(), \'upper\') : (($this->instanceCall($__lumiVariables[\'user\'])->name()) | ($__lumiVariables[\'upper\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithBinaryOpRightEmitsPlainBitwiseOr(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'flags',
                ),
                right: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'a',
                    ),
                    right: new IdentifierNode(
                        name: 'b',
                    ),
                    operator: BinarySymbol::BITWISE_AND,
                ),
            ),
        );

        self::assertSame(
            '(($__lumiVariables[\'flags\']) | ($__lumiVariables[\'a\'] & $__lumiVariables[\'b\']))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithArrayAccessRightEmitsPlainBitwiseOr(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'flags',
                ),
                right: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'masks',
                    ),
                    key: LiteralNode::createString('write'),
                ),
            ),
        );

        self::assertSame(
            '(($__lumiVariables[\'flags\']) | ($__lumiVariables[\'masks\'][\'write\']))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithMethodCallRightEmitsPlainBitwiseOr(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'flags',
                ),
                right: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'mask',
                    ),
                    name: 'value',
                    arguments: [],
                ),
            ),
        );

        self::assertSame(
            '(($__lumiVariables[\'flags\']) | ($this->instanceCall($__lumiVariables[\'mask\'])->value()))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithGroupedRightEmitsPlainBitwiseOr(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'flags',
                ),
                right: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'mask',
                    ),
                ),
            ),
        );

        self::assertSame(
            '(($__lumiVariables[\'flags\']) | (($__lumiVariables[\'mask\'])))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrWithPropertyAccessRightEmitsPlainBitwiseOr(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new IdentifierNode(
                    name: 'flags',
                ),
                right: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'mask',
                    ),
                    property: 'value',
                ),
            ),
        );

        self::assertSame(
            '(($__lumiVariables[\'flags\']) | ($this->propertyAccess($__lumiVariables[\'mask\'])->value))',
            $output,
        );
    }

    public function testCompileFilterOrBitwiseOrChainsViaNestedNode(): void
    {
        $output = $this->compiler->compileExpression(
            new FilterOrBitwiseOrNode(
                left: new FilterOrBitwiseOrNode(
                    left: new IdentifierNode(
                        name: 'name',
                    ),
                    right: new IdentifierNode(
                        name: 'upper',
                    ),
                ),
                right: new IdentifierNode(
                    name: 'trim',
                ),
            ),
        );

        self::assertSame(
            '($this->hasFilter(\'trim\') ? $this->filter(($this->hasFilter(\'upper\') ? $this->filter($__lumiVariables[\'name\'], \'upper\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'upper\']))), \'trim\') : ((($this->hasFilter(\'upper\') ? $this->filter($__lumiVariables[\'name\'], \'upper\') : (($__lumiVariables[\'name\']) | ($__lumiVariables[\'upper\'])))) | ($__lumiVariables[\'trim\'])))',
            $output,
        );
    }

    public function testCompileArrayAccessEmitsArrayBracketKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame('$__lumiVariables[\'items\'][0]', $output);
    }

    public function testCompileArrayAccessSupportsStringKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'config',
                ),
                key: LiteralNode::createString('locale'),
            ),
        );

        self::assertSame('$__lumiVariables[\'config\'][\'locale\']', $output);
    }

    public function testCompileArrayAccessSupportsIdentifierKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new IdentifierNode(
                    name: 'index',
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][$__lumiVariables[\'index\']]',
            $output,
        );
    }

    public function testCompileArrayAccessSupportsBoolKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'lookup',
                ),
                key: LiteralNode::createBool(true),
            ),
        );

        self::assertSame('$__lumiVariables[\'lookup\'][true]', $output);
    }

    public function testCompileArrayAccessSupportsNullKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'lookup',
                ),
                key: LiteralNode::createNull(),
            ),
        );

        self::assertSame('$__lumiVariables[\'lookup\'][null]', $output);
    }

    public function testCompileArrayAccessSupportsFloatKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'lookup',
                ),
                key: new LiteralNode(
                    operand: '1.5',
                    type: Type::FLOAT,
                ),
            ),
        );

        self::assertSame('$__lumiVariables[\'lookup\'][1.5]', $output);
    }

    public function testCompileArrayAccessSupportsBinaryOpKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'i',
                    ),
                    right: LiteralNode::createInt(1),
                    operator: BinarySymbol::ADD,
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][$__lumiVariables[\'i\'] + 1]',
            $output,
        );
    }

    public function testCompileArrayAccessSupportsMethodCallKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'cursor',
                    ),
                    name: 'current',
                    arguments: [],
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][$this->instanceCall($__lumiVariables[\'cursor\'])->current()]',
            $output,
        );
    }

    public function testCompileArrayAccessSupportsFunctionCallKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'first',
                    ),
                    arguments: [],
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][$this->functionCall(\'first\', [])]',
            $output,
        );
    }

    public function testCompileArrayAccessSupportsGroupKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'i',
                    ),
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][($__lumiVariables[\'i\'])]',
            $output,
        );
    }

    public function testCompileArrayAccessChainsWithNestedArrayAccess(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'matrix',
                    ),
                    key: LiteralNode::createInt(0),
                ),
                key: LiteralNode::createInt(1),
            ),
        );

        self::assertSame('$__lumiVariables[\'matrix\'][0][1]', $output);
    }

    public function testCompileArrayAccessSupportsArrayAccessKey(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
                key: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'lookup',
                    ),
                    key: LiteralNode::createString('current'),
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'items\'][$__lumiVariables[\'lookup\'][\'current\']]',
            $output,
        );
    }

    public function testCompileArrayAccessOnMethodCallArray(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'service',
                    ),
                    name: 'list',
                    arguments: [],
                ),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame(
            '$this->instanceCall($__lumiVariables[\'service\'])->list()[0]',
            $output,
        );
    }

    public function testCompileArrayAccessOnPropertyAccessArray(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'user',
                    ),
                    property: 'roles',
                ),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame(
            '$this->propertyAccess($__lumiVariables[\'user\'])->roles[0]',
            $output,
        );
    }

    public function testCompileArrayAccessOnFunctionCallArray(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'all',
                    ),
                    arguments: [],
                ),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame(
            '$this->functionCall(\'all\', [])[0]',
            $output,
        );
    }

    public function testCompileArrayAccessOnGroupedArray(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'items',
                    ),
                ),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame('($__lumiVariables[\'items\'])[0]', $output);
    }

    public function testCompileArrayAccessOnArrayLiteralArray(): void
    {
        $output = $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new ArrayNode(
                    items: [
                        new ArrayItemNode(
                            value: LiteralNode::createInt(10),
                        ),
                        new ArrayItemNode(
                            value: LiteralNode::createInt(20),
                        ),
                    ],
                ),
                key: LiteralNode::createInt(1),
            ),
        );

        self::assertSame('[10, 20][1]', $output);
    }

    public function testCompileArrayAccessThrowsWhenKeyIsNull(): void
    {
        self::expectException(CompilerException::class);

        $this->compiler->compileExpression(
            new ArrayAccessNode(
                array: new IdentifierNode(
                    name: 'items',
                ),
            ),
        );
    }

    private function compileArrayItemInExpressionScope(
        ArrayItemNode $node,
    ): string {
        $oldScope = $this->compiler->state->swap(NodeScope::EXPRESSION);

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        $this->compiler->state->swap($oldScope);

        return $output;
    }

    public function testCompileArrayItemWithoutKeyEmitsValueOnly(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createInt(1),
            ),
        );

        self::assertSame('1', $output);
    }

    public function testCompileArrayItemWithoutKeyEmitsIdentifierValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: new IdentifierNode(
                    name: 'item',
                ),
            ),
        );

        self::assertSame('$__lumiVariables[\'item\']', $output);
    }

    public function testCompileArrayItemWithoutKeyEmitsStringValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('hello'),
            ),
        );

        self::assertSame('\'hello\'', $output);
    }

    public function testCompileArrayItemWithoutKeyEmitsBoolValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createBool(true),
            ),
        );

        self::assertSame('true', $output);
    }

    public function testCompileArrayItemWithoutKeyEmitsNullValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createNull(),
            ),
        );

        self::assertSame('null', $output);
    }

    public function testCompileArrayItemWithoutKeyEmitsExpressionValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: new BinaryOpNode(
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

        self::assertSame(
            '$__lumiVariables[\'a\'] + $__lumiVariables[\'b\']',
            $output,
        );
    }

    public function testCompileArrayItemWithStringKeyAndIntValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createInt(42),
                key: LiteralNode::createString('answer'),
            ),
        );

        self::assertSame('\'answer\' => 42', $output);
    }

    public function testCompileArrayItemWithIntKeyAndStringValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('zero'),
                key: LiteralNode::createInt(0),
            ),
        );

        self::assertSame('0 => \'zero\'', $output);
    }

    public function testCompileArrayItemWithIdentifierKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: new IdentifierNode(
                    name: 'value',
                ),
                key: new IdentifierNode(
                    name: 'name',
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'name\'] => $__lumiVariables[\'value\']',
            $output,
        );
    }

    public function testCompileArrayItemWithBoolKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('yes'),
                key: LiteralNode::createBool(true),
            ),
        );

        self::assertSame('true => \'yes\'', $output);
    }

    public function testCompileArrayItemWithNullKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('nada'),
                key: LiteralNode::createNull(),
            ),
        );

        self::assertSame('null => \'nada\'', $output);
    }

    public function testCompileArrayItemWithFloatKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('half'),
                key: new LiteralNode(
                    operand: '0.5',
                    type: Type::FLOAT,
                ),
            ),
        );

        self::assertSame('0.5 => \'half\'', $output);
    }

    public function testCompileArrayItemWithBinaryOpKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createString('computed'),
                key: new BinaryOpNode(
                    left: new IdentifierNode(
                        name: 'i',
                    ),
                    right: LiteralNode::createInt(1),
                    operator: BinarySymbol::ADD,
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'i\'] + 1 => \'computed\'',
            $output,
        );
    }

    public function testCompileArrayItemWithFunctionCallKey(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: LiteralNode::createInt(1),
                key: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'slug',
                    ),
                    arguments: [
                        LiteralNode::createString('hello'),
                    ],
                ),
            ),
        );

        self::assertSame(
            '$this->functionCall(\'slug\', [\'hello\']) => 1',
            $output,
        );
    }

    public function testCompileArrayItemWithMethodCallValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'user',
                    ),
                    name: 'name',
                    arguments: [],
                ),
                key: LiteralNode::createString('label'),
            ),
        );

        self::assertSame(
            '\'label\' => $this->instanceCall($__lumiVariables[\'user\'])->name()',
            $output,
        );
    }

    public function testCompileArrayItemWithArrayAccessKeyAndValue(): void
    {
        $output = $this->compileArrayItemInExpressionScope(
            new ArrayItemNode(
                value: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'values',
                    ),
                    key: LiteralNode::createInt(0),
                ),
                key: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'keys',
                    ),
                    key: LiteralNode::createInt(0),
                ),
            ),
        );

        self::assertSame(
            '$__lumiVariables[\'keys\'][0] => $__lumiVariables[\'values\'][0]',
            $output,
        );
    }

    public function testCompileFunctionCallThrowsWhenNameIsNotIdentifier(): void
    {
        self::expectException(CompilerException::class);

        $this->compiler->compileExpression(
            new FunctionCallNode(
                name: LiteralNode::createString('upper'),
                arguments: [],
            ),
        );
    }

    public function testCompileFunctionCallThrowsWhenNameIsMethodCall(): void
    {
        self::expectException(CompilerException::class);

        $this->compiler->compileExpression(
            new FunctionCallNode(
                name: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'registry',
                    ),
                    name: 'resolver',
                    arguments: [],
                ),
                arguments: [],
            ),
        );
    }

    public function testCompilePropertyAccessThrowsOnNullSafeAccessorInAssignmentTarget(): void
    {
        self::expectException(CompilerException::class);

        $this->compileNode(
            new AssignmentNode(
                name: new PropertyAccessNode(
                    accessor: new PropertyAccessNode(
                        accessor: new IdentifierNode(
                            name: 'user',
                        ),
                        property: 'profile',
                        nullSafe: true,
                    ),
                    property: 'name',
                ),
                value: LiteralNode::createString('Kalle'),
                operator: AssignmentSymbol::ASSIGN,
            ),
        );
    }
}
