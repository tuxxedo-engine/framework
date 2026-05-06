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

use Fixture\View\Lumi\Compiler\Compiler\FooNode;
use Fixture\View\Lumi\Compiler\Compiler\FooProvider;
use Fixture\View\Lumi\Compiler\Compiler\LiteralNodeStubProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Compiler\RecordingExpressionCompiler;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\TextCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\IncludeNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\LumiNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Type;

class TextCompilerProviderTest extends TestCase
{
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new TextCompilerProvider(),
                new FooProvider(),
                new LiteralNodeStubProvider(),
            ],
            expressionCompiler: new RecordingExpressionCompiler(),
        );

        $this->compiler->state->enter(NodeScope::STATEMENT);
    }

    public function testCompilesPlainTextNode(): void
    {
        $node = new TextNode(
            text: 'hello world',
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('hello world', $output);
    }

    public function testCompilesTextNodeStripsPhpOpeningTag(): void
    {
        $node = new TextNode(
            text: '<?php payload',
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('&lt;?php payload', $output);
    }

    public function testCompilesTextNodeInLayoutStreamOutsideBlockReturnsEmpty(): void
    {
        $textNode = new TextNode(
            text: 'visible',
        );

        $output = $this->compiler->compileNode(
            node: $textNode,
            stream: new NodeStream(
                nodes: [
                    new LayoutNode(
                        file: 'layouts/base.lumi',
                    ),
                    $textNode,
                ],
            ),
        );

        self::assertSame('', $output);
    }

    public function testCompilesCommentNodeWithStripCommentsTrueByDefault(): void
    {
        $node = new CommentNode(
            text: 'a note',
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('', $output);
    }

    public function testCompilesCommentNodeWhenStripCommentsDisabled(): void
    {
        $this->compiler->state->directives->set(
            'lumi.strip_comments',
            false,
        );

        $node = new CommentNode(
            text: 'a note',
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame("<?php // a note ?>\n", $output);
    }

    public function testCompilesMultilineCommentNodeAsMultipleSingleLineComments(): void
    {
        $this->compiler->state->directives->set(
            'lumi.strip_comments',
            false,
        );

        $node = new CommentNode(
            text: "first line\nsecond line",
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame("<?php // first line ?>\n<?php // second line ?>\n", $output);
    }

    public function testCompilesEchoNodeWithAutoescapeAppliesFilter(): void
    {
        $node = new EchoNode(
            operand: new IdentifierNode(
                name: 'user',
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            "<?= \$this->filter(/* expr */, 'escape_html'); ?>",
            $output,
        );
    }

    public function testCompilesEchoNodeWithRawContextSkipsFilter(): void
    {
        $node = new EchoNode(
            operand: new IdentifierNode(
                name: 'user',
            ),
            context: TextContext::RAW,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesEchoNodeWithAutoescapeDisabledSkipsFilter(): void
    {
        $this->compiler->state->directives->set(
            'lumi.autoescape',
            false,
        );

        $node = new EchoNode(
            operand: new IdentifierNode(
                name: 'user',
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesEchoNodeWithIntLiteralSkipsFilterEvenWithAutoescape(): void
    {
        $node = new EchoNode(
            operand: new LiteralNode(
                operand: '42',
                type: Type::INT,
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesDeclareNodeRegistersDirectiveAndEmitsRuntimeCall(): void
    {
        $node = new DeclareNode(
            directive: LiteralNode::createString('theme'),
            value: LiteralNode::createString('darkmode'),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            '<?php $this->directive(<STRING:theme>, <STRING:darkmode>); ?>',
            $output,
        );

        self::assertSame(
            'darkmode',
            $this->compiler->state->directives->asString('theme'),
        );
    }

    public function testCompilesDeclareNodeWithIntValue(): void
    {
        $node = new DeclareNode(
            directive: LiteralNode::createString('maxRetries'),
            value: LiteralNode::createInt(5),
        );

        $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            5,
            $this->compiler->state->directives->asInt('maxRetries'),
        );
    }

    public function testCompilesDeclareNodeWithFloatValue(): void
    {
        $node = new DeclareNode(
            directive: LiteralNode::createString('ratio'),
            value: LiteralNode::createFloat(2.5),
        );

        $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            2.5,
            $this->compiler->state->directives->asFloat('ratio'),
        );
    }

    public function testCompilesDeclareNodeWithBoolValue(): void
    {
        $node = new DeclareNode(
            directive: LiteralNode::createString('debug'),
            value: LiteralNode::createBool(true),
        );

        $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertTrue(
            $this->compiler->state->directives->asBool('debug'),
        );
    }

    public function testCompilesDeclareNodeWithNullValue(): void
    {
        $node = new DeclareNode(
            directive: LiteralNode::createString('fallback'),
            value: LiteralNode::createNull(),
        );

        $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertTrue(
            $this->compiler->state->directives->isNull('fallback'),
        );
    }

    public function testCompilesEchoNodeWithUnaryOpBypassesAutoescape(): void
    {
        $node = new EchoNode(
            operand: new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'flag',
                ),
                operator: UnarySymbol::NOT,
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesEchoNodeWithBinaryOpOfNonStringLiteralsBypassesAutoescape(): void
    {
        $node = new EchoNode(
            operand: new BinaryOpNode(
                left: new LiteralNode(
                    operand: '1',
                    type: Type::INT,
                ),
                right: new LiteralNode(
                    operand: '2',
                    type: Type::INT,
                ),
                operator: BinarySymbol::ADD,
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesEchoNodeWithGroupedNonStringLiteralBypassesAutoescape(): void
    {
        $node = new EchoNode(
            operand: new GroupNode(
                operand: new LiteralNode(
                    operand: '42',
                    type: Type::INT,
                ),
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?= /* expr */; ?>', $output);
    }

    public function testCompilesBlockNodeOutsideLayoutStream(): void
    {
        $node = new BlockNode(
            name: 'sidebar',
            body: [
                new FooNode(),
            ],
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            "<?php if (\$this->hasBlock('sidebar')) { \$this->executeBlock('sidebar', \$__lumiVariables); } else { ?>/* foo */<?php } ?>",
            $output,
        );
    }

    public function testCompilesBlockNodeInLayoutStream(): void
    {
        $blockNode = new BlockNode(
            name: 'content',
            body: [
                new FooNode(),
            ],
        );

        $output = $this->compiler->compileNode(
            node: $blockNode,
            stream: new NodeStream(
                nodes: [
                    new LayoutNode(
                        file: 'layouts/base.lumi',
                    ),
                    $blockNode,
                ],
            ),
        );

        self::assertSame(
            "\n<?php \$this->block('content', function (array &\$__lumiVariables): void { ?>/* foo */<?php }); ?>",
            $output,
        );
    }

    public function testCompilesLumiNode(): void
    {
        $node = new LumiNode(
            theme: 'github-dark',
            sourceCode: '{% if user %}hi{% endif %}',
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame(
            "<?php \$this->highlight('github-dark', '{% if user %}hi{% endif %}'); ?>",
            $output,
        );
    }

    public function testCompilesIncludeNodeWithoutScope(): void
    {
        $node = new IncludeNode(
            file: new LiteralNode(
                operand: 'partials/header.lumi',
                type: Type::STRING,
            ),
            scope: null,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php $this->include(/* expr */); ?>', $output);
    }

    public function testCompilesIncludeNodeWithScope(): void
    {
        $node = new IncludeNode(
            file: new LiteralNode(
                operand: 'partials/header.lumi',
                type: Type::STRING,
            ),
            scope: new ArrayNode(
                items: [],
            ),
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php $this->include(/* expr */, /* expr */); ?>', $output);
    }

    public function testCompilesLayoutNodeAsPostHandlerAfterMainPass(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new TextCompilerProvider(),
                new FooProvider(),
            ],
            expressionCompiler: new RecordingExpressionCompiler(),
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new BlockNode(
                        name: 'content',
                        body: [],
                    ),
                    new LayoutNode(
                        file: 'layouts/base.lumi',
                    ),
                ],
            ),
        );

        self::assertStringEndsWith(
            "\n<?php \$this->layout('layouts/base.lumi', \$__lumiVariables); ?>",
            $output,
        );
    }
}
