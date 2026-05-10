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

namespace Unit\View\Lumi\Highlight;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Highlight\ColorSlot;
use Tuxxedo\View\Lumi\Highlight\HighlightException;
use Tuxxedo\View\Lumi\Highlight\Highlighter;
use Tuxxedo\View\Lumi\Highlight\HighlighterInterface;
use Tuxxedo\View\Lumi\Highlight\Theme\LumiDark;
use Tuxxedo\View\Lumi\Highlight\Theme\LumiLight;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConcatNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\LumiNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;

class HighlighterTest extends TestCase
{
    private Highlighter $highlighter;
    private EscaperInterface $escaper;

    protected function setUp(): void
    {
        $this->highlighter = new Highlighter();
        $this->escaper = new Escaper();
    }

    /**
     * @return \Generator<array{0: ThemeInterface}>
     */
    public static function provideThemes(): \Generator
    {
        yield [
            new LumiLight(),
        ];

        yield [
            new LumiDark(),
        ];
    }

    private function dye(
        ThemeInterface $theme,
        ColorSlot $slot,
        string $text,
    ): string {
        return \sprintf(
            '<span style="color: %s;">%s</span>',
            $theme->color($slot),
            $text,
        );
    }

    /**
     * @param NodeInterface[] $nodes
     */
    private function highlightNodes(
        ThemeInterface|string $theme,
        array $nodes,
    ): string {
        return $this->highlighter->highlight(
            theme: $theme,
            stream: new NodeStream(
                nodes: $nodes,
            ),
        );
    }

    private function highlight(
        ThemeInterface|string $theme,
        NodeInterface $node,
    ): string {
        return $this->highlightNodes(
            theme: $theme,
            nodes: [
                $node,
            ],
        );
    }

    private function expectedLiteralString(
        ThemeInterface $theme,
        string $operand,
    ): string {
        $quote = \str_contains($operand, '\"')
            ? '"'
            : '\'';

        return $this->dye(
            theme: $theme,
            slot: ColorSlot::STRING,
            text: $this->escaper->html($quote . $operand . $quote),
        );
    }

    public function testImplementsHighlighterInterface(): void
    {
        self::assertInstanceOf(
            HighlighterInterface::class,
            $this->highlighter,
        );
    }

    public function testHighlightEmptyStreamReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->highlightNodes(
                theme: new LumiLight(),
                nodes: [],
            ),
        );
    }

    /**
     * @return \Generator<array{0: string, 1: ThemeInterface}>
     */
    public static function provideThemeIdentifiers(): \Generator
    {
        yield [
            'light',
            new LumiLight(),
        ];

        yield [
            'dark',
            new LumiDark(),
        ];

        yield [
            'LIGHT',
            new LumiLight(),
        ];

        yield [
            'Dark',
            new LumiDark(),
        ];
    }

    #[DataProvider('provideThemeIdentifiers')]
    public function testHighlightAcceptsThemeIdentifierString(
        string $identifier,
        ThemeInterface $expectedTheme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $expectedTheme,
                slot: ColorSlot::IDENTIFIER,
                text: 'foo',
            ),
            $this->highlight(
                theme: $identifier,
                node: new IdentifierNode(
                    name: 'foo',
                ),
            ),
        );
    }

    public function testHighlightThrowsForUnknownThemeIdentifier(): void
    {
        self::expectException(HighlightException::class);

        $this->highlightNodes(
            theme: 'fuchsia',
            nodes: [],
        );
    }

    public function testHighlightThrowsForUnknownNodeType(): void
    {
        $unknown = new class () implements NodeInterface {
            public array $scopes = [];
        };

        self::expectException(HighlightException::class);

        $this->highlight(
            theme: new LumiLight(),
            node: $unknown,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightConcatenatesMultipleNodes(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'a',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'b',
            );

        self::assertSame(
            $expected,
            $this->highlightNodes(
                theme: $theme,
                nodes: [
                    new IdentifierNode(
                        name: 'a',
                    ),
                    new IdentifierNode(
                        name: 'b',
                    ),
                ],
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightIdentifierNode(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'user',
            ),
            $this->highlight(
                theme: $theme,
                node: new IdentifierNode(
                    name: 'user',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralIntNode(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '42',
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createInt(42),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralFloatNode(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '3.14',
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createFloat(3.14),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralBoolNode(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::BOOL,
                text: 'true',
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralNullNode(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NULL,
                text: 'null',
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createNull(),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralStringNodeUsesSingleQuotes(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->expectedLiteralString(
                theme: $theme,
                operand: 'hello',
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createString('hello'),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralStringNodeUsesDoubleQuotesWhenContainsEscapedDoubleQuote(
        ThemeInterface $theme,
    ): void {
        $operand = 'has\\"quote';

        self::assertSame(
            $this->expectedLiteralString(
                theme: $theme,
                operand: $operand,
            ),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createString($operand),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLiteralStringNodeEscapesHtml(
        ThemeInterface $theme,
    ): void {
        self::assertStringContainsString(
            $this->escaper->html('\'<x>\''),
            $this->highlight(
                theme: $theme,
                node: LiteralNode::createString('<x>'),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightTextNodeNoneContext(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: $this->escaper->html('<hello>'),
            ),
            $this->highlight(
                theme: $theme,
                node: new TextNode(
                    text: '<hello>',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightTextNodeRawContext(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'raw',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: $this->escaper->html('<x>'),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '{% ',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endraw',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new TextNode(
                    text: '<x>',
                    context: TextContext::RAW,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightCommentNodeEscapesAndWraps(
        ThemeInterface $theme,
    ): void {
        self::assertSame(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::COMMENT,
                text: \sprintf(
                    '{# %s #}',
                    $this->escaper->html('hi <there>'),
                ),
            ),
            $this->highlight(
                theme: $theme,
                node: new CommentNode(
                    text: 'hi <there>',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightEchoNodeNoneContext(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{{ ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'name',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' }}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new EchoNode(
                    operand: new IdentifierNode(
                        name: 'name',
                    ),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightEchoNodeRawContext(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{! ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'name',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' !}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new EchoNode(
                    operand: new IdentifierNode(
                        name: 'name',
                    ),
                    context: TextContext::RAW,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightArrayNodeEmpty(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '[',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ']',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ArrayNode(
                    items: [],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightArrayNodeWithKeyedAndUnkeyedItems(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '[',
        ) .
            $this->expectedLiteralString(
                theme: $theme,
                operand: 'key',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ':',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ', ',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '2',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ']',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ArrayNode(
                    items: [
                        new ArrayItemNode(
                            value: LiteralNode::createInt(1),
                            key: LiteralNode::createString('key'),
                        ),
                        new ArrayItemNode(
                            value: LiteralNode::createInt(2),
                        ),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightArrayAccessNodeWithKey(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'arr',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '[',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '0',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ']',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'arr',
                    ),
                    key: LiteralNode::createInt(0),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightArrayAccessNodeWithoutKey(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'arr',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '[',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ']',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ArrayAccessNode(
                    array: new IdentifierNode(
                        name: 'arr',
                    ),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightAssignmentNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'set',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'x',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::OPERATOR,
                text: $this->escaper->html(AssignmentSymbol::ASSIGN->symbol()),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'x',
                    ),
                    value: LiteralNode::createInt(1),
                    operator: AssignmentSymbol::ASSIGN,
                ),
            ),
        );
    }

    /**
     * @return \Generator<array{0: ThemeInterface, 1: BinarySymbol, 2: ColorSlot}>
     */
    public static function provideBinaryOperatorSlots(): \Generator
    {
        foreach (self::provideThemes() as [$theme]) {
            yield [
                $theme,
                BinarySymbol::ADD,
                ColorSlot::OPERATOR,
            ];

            yield [
                $theme,
                BinarySymbol::CONCAT,
                ColorSlot::CONCAT,
            ];

            yield [
                $theme,
                BinarySymbol::NULL_COALESCE,
                ColorSlot::NULL_COALESCE,
            ];
        }
    }

    #[DataProvider('provideBinaryOperatorSlots')]
    public function testHighlightBinaryOpNodeUsesSlotPerOperator(
        ThemeInterface $theme,
        BinarySymbol $operator,
        ColorSlot $expectedSlot,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::NUMBER,
            text: '1',
        ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: $expectedSlot,
                text: $this->escaper->html($operator->symbol()),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '2',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new BinaryOpNode(
                    left: LiteralNode::createInt(1),
                    right: LiteralNode::createInt(2),
                    operator: $operator,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightBlockNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'block',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'main',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: $this->escaper->html('body'),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '{% ',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endblock',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new BlockNode(
                    name: 'main',
                    body: [
                        new TextNode(
                            text: 'body',
                        ),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightBreakNodeWithoutCount(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'break',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new BreakNode(),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightBreakNodeWithCount(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'break',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '2',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new BreakNode(
                    count: 2,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightContinueNodeWithoutCount(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'continue',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ContinueNode(),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightContinueNodeWithCount(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'continue',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '3',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ContinueNode(
                    count: 3,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightConcatNode(
        ThemeInterface $theme,
    ): void {
        $separator = ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::CONCAT,
                text: BinarySymbol::CONCAT->symbol(),
            ) .
            ' ';

        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'a',
        ) .
            $separator .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'b',
            ) .
            $separator .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'c',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ConcatNode(
                    operands: [
                        new IdentifierNode(
                            name: 'a',
                        ),
                        new IdentifierNode(
                            name: 'b',
                        ),
                        new IdentifierNode(
                            name: 'c',
                        ),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightConditionalNodeIfOnly(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'if',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'cond',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: $this->escaper->html('hi'),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '{% ',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endif',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new ConditionalNode(
                    operand: new IdentifierNode(
                        name: 'cond',
                    ),
                    body: [
                        new TextNode(
                            text: 'hi',
                        ),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightConditionalNodeWithElseIfAndElse(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new ConditionalNode(
                operand: new IdentifierNode(
                    name: 'a',
                ),
                body: [
                    new TextNode(
                        text: 'top',
                    ),
                ],
                branches: [
                    new ConditionalBranchNode(
                        operand: new IdentifierNode(
                            name: 'b',
                        ),
                        body: [
                            new TextNode(
                                text: 'mid',
                            ),
                        ],
                    ),
                ],
                else: [
                    new TextNode(
                        text: 'fallback',
                    ),
                ],
            ),
        );

        $elseif = $this->dye(
            theme: $theme,
            slot: ColorSlot::KEYWORD,
            text: 'elseif',
        );

        $else = $this->dye(
            theme: $theme,
            slot: ColorSlot::KEYWORD,
            text: 'else',
        );

        self::assertStringContainsString($elseif, $output);
        self::assertStringContainsString($else, $output);
        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'top',
            ),
            $output,
        );
        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'mid',
            ),
            $output,
        );
        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'fallback',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightDeclareNodeSimpleDirective(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'declare',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'strict_types',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::OPERATOR,
                text: AssignmentSymbol::ASSIGN->symbol(),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new DeclareNode(
                    directive: LiteralNode::createString('strict_types'),
                    value: LiteralNode::createInt(1),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightDeclareNodeDottedDirective(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'declare',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'ticks',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '.',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'value',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::OPERATOR,
                text: AssignmentSymbol::ASSIGN->symbol(),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new DeclareNode(
                    directive: LiteralNode::createString('ticks.value'),
                    value: LiteralNode::createInt(1),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightDoWhileNode(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new DoWhileNode(
                operand: new IdentifierNode(
                    name: 'cond',
                ),
                body: [
                    new TextNode(
                        text: 'body',
                    ),
                ],
            ),
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'do',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'while',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'body',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightWhileNode(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new WhileNode(
                operand: new IdentifierNode(
                    name: 'cond',
                ),
                body: [
                    new TextNode(
                        text: 'x',
                    ),
                ],
            ),
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'while',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endwhile',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'x',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightForNodeWithoutKey(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new ForNode(
                value: new IdentifierNode(
                    name: 'item',
                ),
                iterator: new IdentifierNode(
                    name: 'items',
                ),
                body: [
                    new TextNode(
                        text: 'body',
                    ),
                ],
            ),
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'for',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'in',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endfor',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'body',
            ),
            $output,
        );

        self::assertStringNotContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ',',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightForNodeWithKey(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new ForNode(
                value: new IdentifierNode(
                    name: 'value',
                ),
                iterator: new IdentifierNode(
                    name: 'items',
                ),
                body: [
                    new TextNode(
                        text: 'body',
                    ),
                ],
                key: new IdentifierNode(
                    name: 'key',
                ),
            ),
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ',',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'key',
            ),
            $output,
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: 'body',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightFunctionCallWithIdentifierName(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::FUNCTION_NAME,
            text: 'foo',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '(',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ',',
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '2',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'foo',
                    ),
                    arguments: [
                        LiteralNode::createInt(1),
                        LiteralNode::createInt(2),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightFunctionCallWithArrayDereference(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'arr',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '[',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '0',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ']',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '(',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new FunctionCallNode(
                    name: new ArrayAccessNode(
                        array: new IdentifierNode(
                            name: 'arr',
                        ),
                        key: LiteralNode::createInt(0),
                    ),
                    arguments: [],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightFunctionCallWithoutArguments(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::FUNCTION_NAME,
            text: 'foo',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '(',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new FunctionCallNode(
                    name: new IdentifierNode(
                        name: 'foo',
                    ),
                    arguments: [],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightGroupNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '(',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'x',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'x',
                    ),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightFilterOrBitwiseOrNodeAsFilterWhenRightIsIdentifier(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'name',
        ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::PIPE,
                text: BinarySymbol::BITWISE_OR->symbol(),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::FILTER_NAME,
                text: 'upper',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new FilterOrBitwiseOrNode(
                    left: new IdentifierNode(
                        name: 'name',
                    ),
                    right: new IdentifierNode(
                        name: 'upper',
                    ),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightFilterOrBitwiseOrNodeAsBitwiseWhenRightIsNotIdentifier(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::NUMBER,
            text: '1',
        ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::OPERATOR,
                text: BinarySymbol::BITWISE_OR->symbol(),
            ) .
            ' ' .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '2',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new FilterOrBitwiseOrNode(
                    left: LiteralNode::createInt(1),
                    right: LiteralNode::createInt(2),
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLayoutNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'layout',
            ) .
            ' ' .
            $this->expectedLiteralString(
                theme: $theme,
                operand: 'base.lumi',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new LayoutNode(
                    file: 'base.lumi',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLumiNodeWithoutTheme(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::DELIMITER,
            text: '{% ',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'lumi',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::TEXT,
                text: $this->escaper->html('<x>'),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '{% ',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::KEYWORD,
                text: 'endlumi',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ' %}',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new LumiNode(
                    theme: '',
                    sourceCode: '<x>',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightLumiNodeWithTheme(
        ThemeInterface $theme,
    ): void {
        $output = $this->highlight(
            theme: $theme,
            node: new LumiNode(
                theme: 'dark',
                sourceCode: 'x',
            ),
        );

        self::assertStringContainsString(
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'dark',
            ),
            $output,
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightMethodCallNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'obj',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '.',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::MEMBER_NAME,
                text: 'doIt',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '(',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::NUMBER,
                text: '1',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'obj',
                    ),
                    name: 'doIt',
                    arguments: [
                        LiteralNode::createInt(1),
                    ],
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightMethodCallNodeNullSafe(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'obj',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: BinarySymbol::NULL_SAFE_ACCESS->symbol(),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::MEMBER_NAME,
                text: 'doIt',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '(',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: ')',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: 'obj',
                    ),
                    name: 'doIt',
                    arguments: [],
                    nullSafe: true,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightPropertyAccessNode(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'obj',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: '.',
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::MEMBER_NAME,
                text: 'prop',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'obj',
                    ),
                    property: 'prop',
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightPropertyAccessNodeNullSafe(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'obj',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::DELIMITER,
                text: BinarySymbol::NULL_SAFE_ACCESS->symbol(),
            ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::MEMBER_NAME,
                text: 'prop',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new PropertyAccessNode(
                    accessor: new IdentifierNode(
                        name: 'obj',
                    ),
                    property: 'prop',
                    nullSafe: true,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightUnaryOpNodePrefix(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::OPERATOR,
            text: $this->escaper->html(UnarySymbol::NOT->symbol()),
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::IDENTIFIER,
                text: 'flag',
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'flag',
                    ),
                    operator: UnarySymbol::NOT,
                ),
            ),
        );
    }

    #[DataProvider('provideThemes')]
    public function testHighlightUnaryOpNodePostfix(
        ThemeInterface $theme,
    ): void {
        $expected = $this->dye(
            theme: $theme,
            slot: ColorSlot::IDENTIFIER,
            text: 'i',
        ) .
            $this->dye(
                theme: $theme,
                slot: ColorSlot::OPERATOR,
                text: $this->escaper->html(UnarySymbol::INCREMENT_POST->symbol()),
            );

        self::assertSame(
            $expected,
            $this->highlight(
                theme: $theme,
                node: new UnaryOpNode(
                    operand: new IdentifierNode(
                        name: 'i',
                    ),
                    operator: UnarySymbol::INCREMENT_POST,
                ),
            ),
        );
    }
}
