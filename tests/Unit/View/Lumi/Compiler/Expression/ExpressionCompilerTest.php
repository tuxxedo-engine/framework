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

namespace Unit\View\Lumi\Compiler\Expression;

use Fixture\View\Lumi\Compiler\Compiler\FooNode;
use Fixture\View\Lumi\Compiler\Compiler\FooProvider;
use Fixture\View\Lumi\Compiler\Compiler\LiteralNodeStubProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompiler;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeScope;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionCompilerTest extends TestCase
{
    private ExpressionCompiler $expressionCompiler;
    private CompilerInterface $compiler;

    protected function setUp(): void
    {
        $this->expressionCompiler = new ExpressionCompiler();
        $this->compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new LiteralNodeStubProvider(),
                new FooProvider(),
            ],
        );

        $this->compiler->state->enter(NodeScope::STATEMENT);
    }

    public function testCompilesSingleExpressionNode(): void
    {
        $output = $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    new LiteralNode(
                        operand: '5',
                        type: Type::INT,
                    ),
                ],
            ),
            compiler: $this->compiler,
        );

        self::assertSame('<INT:5>', $output);
    }

    public function testRestoresOriginalScopeAfterCompiling(): void
    {
        $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    new LiteralNode(
                        operand: '5',
                        type: Type::INT,
                    ),
                ],
            ),
            compiler: $this->compiler,
        );

        self::assertSame(
            NodeScope::STATEMENT,
            $this->compiler->state->expects,
        );
    }

    public function testDoesNotSwapWhenAlreadyInExpressionAssignScope(): void
    {
        $this->compiler->state->leave(NodeScope::STATEMENT);
        $this->compiler->state->enter(NodeScope::EXPRESSION_ASSIGN);

        $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    new LiteralNode(
                        operand: '5',
                        type: Type::INT,
                    ),
                ],
            ),
            compiler: $this->compiler,
        );

        self::assertSame(
            NodeScope::EXPRESSION_ASSIGN,
            $this->compiler->state->expects,
        );
    }

    public function testThrowsOnNonExpressionNode(): void
    {
        self::expectException(CompilerException::class);

        $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    new FooNode(),
                ],
            ),
            compiler: $this->compiler,
        );
    }

    public function testThrowsWhenStreamHasMoreThanOneExpressionNode(): void
    {
        self::expectException(CompilerException::class);

        $this->expressionCompiler->compile(
            stream: new NodeStream(
                nodes: [
                    new LiteralNode(
                        operand: '1',
                        type: Type::INT,
                    ),
                    new LiteralNode(
                        operand: '2',
                        type: Type::INT,
                    ),
                ],
            ),
            compiler: $this->compiler,
        );
    }
}
