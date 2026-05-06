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
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Compiler\RecordingExpressionCompiler;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\LoopCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\BreakNode;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Node\DoWhileNode;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Node\WhileNode;

class LoopCompilerProviderTest extends TestCase
{
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new LoopCompilerProvider(),
                new FooProvider(),
            ],
            expressionCompiler: new RecordingExpressionCompiler(),
        );

        $this->compiler->state->enter(NodeScope::STATEMENT);
    }

    public function testCompilesEmptyWhileLoop(): void
    {
        $node = new WhileNode(
            operand: new IdentifierNode(
                name: 'running',
            ),
            body: [],
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
            '<?php while (/* expr */): ?><?php endwhile; ?>',
            $output,
        );
    }

    public function testCompilesWhileLoopWithBody(): void
    {
        $node = new WhileNode(
            operand: new IdentifierNode(
                name: 'running',
            ),
            body: [
                new FooNode(),
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
            '<?php while (/* expr */): ?>/* foo *//* foo */<?php endwhile; ?>',
            $output,
        );
    }

    public function testCompilesEmptyDoWhileLoop(): void
    {
        $node = new DoWhileNode(
            operand: new IdentifierNode(
                name: 'running',
            ),
            body: [],
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
            '<?php do { ?><?php } while (/* expr */); ?>',
            $output,
        );
    }

    public function testCompilesDoWhileLoopWithBody(): void
    {
        $node = new DoWhileNode(
            operand: new IdentifierNode(
                name: 'running',
            ),
            body: [
                new FooNode(),
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
            '<?php do { ?>/* foo *//* foo */<?php } while (/* expr */); ?>',
            $output,
        );
    }

    public function testCompilesContinueWithoutCount(): void
    {
        $node = new ContinueNode();

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php continue; ?>', $output);
    }

    public function testCompilesContinueWithCountOneEmitsBareContinue(): void
    {
        $node = new ContinueNode(
            count: 1,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php continue; ?>', $output);
    }

    public function testCompilesContinueWithCountAboveOne(): void
    {
        $node = new ContinueNode(
            count: 3,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php continue 3; ?>', $output);
    }

    public function testCompilesBreakWithoutCount(): void
    {
        $node = new BreakNode();

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php break; ?>', $output);
    }

    public function testCompilesBreakWithCountOneEmitsBareBreak(): void
    {
        $node = new BreakNode(
            count: 1,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php break; ?>', $output);
    }

    public function testCompilesBreakWithCountAboveOne(): void
    {
        $node = new BreakNode(
            count: 2,
        );

        $output = $this->compiler->compileNode(
            node: $node,
            stream: new NodeStream(
                nodes: [
                    $node,
                ],
            ),
        );

        self::assertSame('<?php break 2; ?>', $output);
    }

    public function testCompilesForLoopWithoutKey(): void
    {
        $node = new ForNode(
            value: new IdentifierNode(
                name: 'item',
            ),
            iterator: new IdentifierNode(
                name: 'items',
            ),
            body: [],
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
            '<?php foreach (/* expr */ as /* expr */): ?><?php endforeach; ?>',
            $output,
        );
    }

    public function testCompilesForLoopWithKey(): void
    {
        $node = new ForNode(
            value: new IdentifierNode(
                name: 'value',
            ),
            iterator: new IdentifierNode(
                name: 'items',
            ),
            body: [],
            key: new IdentifierNode(
                name: 'key',
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
            '<?php foreach (/* expr */ as /* expr */ => /* expr */): ?><?php endforeach; ?>',
            $output,
        );
    }

    public function testCompilesForLoopWithBody(): void
    {
        $node = new ForNode(
            value: new IdentifierNode(
                name: 'item',
            ),
            iterator: new IdentifierNode(
                name: 'items',
            ),
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
            '<?php foreach (/* expr */ as /* expr */): ?>/* foo */<?php endforeach; ?>',
            $output,
        );
    }
}
