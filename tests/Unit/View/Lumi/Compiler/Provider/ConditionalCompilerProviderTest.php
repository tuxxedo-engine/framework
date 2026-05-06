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

use Fixture\View\Lumi\Compiler\Compiler\BarNode;
use Fixture\View\Lumi\Compiler\Compiler\BarProvider;
use Fixture\View\Lumi\Compiler\Compiler\FooNode;
use Fixture\View\Lumi\Compiler\Compiler\FooProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Compiler\RecordingExpressionCompiler;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Provider\ConditionalCompilerProvider;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;

class ConditionalCompilerProviderTest extends TestCase
{
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new ConditionalCompilerProvider(),
                new FooProvider(),
                new BarProvider(),
            ],
            expressionCompiler: new RecordingExpressionCompiler(),
        );

        $this->compiler->state->enter(NodeScope::STATEMENT);
    }

    public function testCompilesIfWithBody(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'user',
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
            '<?php if (/* expr */): ?>/* foo */<?php endif; ?>',
            $output,
        );
    }

    public function testCompilesIfWithElseBranch(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'user',
            ),
            body: [
                new FooNode(),
            ],
            else: [
                new BarNode(),
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
            '<?php if (/* expr */): ?>/* foo */<?php else: ?>/* bar */<?php endif; ?>',
            $output,
        );
    }

    public function testCompilesIfWithElseIfBranch(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'a',
            ),
            body: [
                new FooNode(),
            ],
            branches: [
                new ConditionalBranchNode(
                    operand: new IdentifierNode(
                        name: 'b',
                    ),
                    body: [
                        new BarNode(),
                    ],
                ),
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
            '<?php if (/* expr */): ?>/* foo */<?php elseif (/* expr */): ?>/* bar */<?php endif; ?>',
            $output,
        );
    }

    public function testCompilesIfWithElseIfAndElse(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'a',
            ),
            body: [
                new FooNode(),
            ],
            branches: [
                new ConditionalBranchNode(
                    operand: new IdentifierNode(
                        name: 'b',
                    ),
                    body: [
                        new BarNode(),
                    ],
                ),
            ],
            else: [
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
            '<?php if (/* expr */): ?>/* foo */<?php elseif (/* expr */): ?>/* bar */<?php else: ?>/* foo */<?php endif; ?>',
            $output,
        );
    }

    public function testCompilesIfWithMultipleElseIfBranches(): void
    {
        $node = new ConditionalNode(
            operand: new IdentifierNode(
                name: 'a',
            ),
            body: [],
            branches: [
                new ConditionalBranchNode(
                    operand: new IdentifierNode(
                        name: 'b',
                    ),
                    body: [
                        new FooNode(),
                    ],
                ),
                new ConditionalBranchNode(
                    operand: new IdentifierNode(
                        name: 'c',
                    ),
                    body: [
                        new BarNode(),
                    ],
                ),
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
            '<?php if (/* expr */): ?><?php elseif (/* expr */): ?>/* foo */<?php elseif (/* expr */): ?>/* bar */<?php endif; ?>',
            $output,
        );
    }
}
