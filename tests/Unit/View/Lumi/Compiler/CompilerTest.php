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

namespace Unit\View\Lumi\Compiler;

use Fixture\View\Lumi\Compiler\Compiler\BarNode;
use Fixture\View\Lumi\Compiler\Compiler\BarProvider;
use Fixture\View\Lumi\Compiler\Compiler\FooNode;
use Fixture\View\Lumi\Compiler\Compiler\FooProvider;
use Fixture\View\Lumi\Compiler\Compiler\OutOfScopeNode;
use Fixture\View\Lumi\Compiler\Compiler\OutOfScopeProvider;
use Fixture\View\Lumi\Compiler\Compiler\PostFooProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Compiler\RecordingExpressionCompiler;
use Tuxxedo\Escaper\Escaper;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerState;
use Tuxxedo\View\Lumi\Compiler\Expression\ExpressionCompiler;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class CompilerTest extends TestCase
{
    private const string PHP_PREFIX = '<?php declare(strict_types=1); ?>';

    public function testCreateDefaultExpressionCompilerReturnsExpressionCompiler(): void
    {
        self::assertInstanceOf(
            ExpressionCompiler::class,
            Compiler::createDefaultExpressionCompiler(),
        );
    }

    public function testCreateDefaultCompilerStateReturnsCompilerState(): void
    {
        self::assertInstanceOf(
            CompilerState::class,
            Compiler::createDefaultCompilerState(),
        );
    }

    public function testCreateDefaultEscaperReturnsEscaper(): void
    {
        self::assertInstanceOf(
            Escaper::class,
            Compiler::createDefaultEscaper(),
        );
    }

    public function testCreateWithoutDefaultProvidersExposesProvidedDependencies(): void
    {
        $expressionCompiler = new ExpressionCompiler();
        $state = new CompilerState();
        $escaper = new Escaper();

        $compiler = Compiler::createWithoutDefaultProviders(
            expressionCompiler: $expressionCompiler,
            state: $state,
            escaper: $escaper,
        );

        self::assertSame($expressionCompiler, $compiler->expressionCompiler);
        self::assertSame($state, $compiler->state);
        self::assertSame($escaper, $compiler->escaper);
    }

    public function testCreateWithoutDefaultProvidersFallsBackToDefaults(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders();

        self::assertInstanceOf(ExpressionCompiler::class, $compiler->expressionCompiler);
        self::assertInstanceOf(CompilerState::class, $compiler->state);
        self::assertInstanceOf(Escaper::class, $compiler->escaper);
    }

    public function testEmptyStreamProducesEmptyOutput(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders();

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [],
            ),
        );

        self::assertSame('', $output);
    }

    public function testCompilesSingleNodeWithPhpPrefix(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new FooProvider(),
            ],
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new FooNode(),
                ],
            ),
        );

        self::assertSame(self::PHP_PREFIX . '/* foo */', $output);
    }

    public function testCompilesMultipleNodesInOrder(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new FooProvider(),
                new BarProvider(),
            ],
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new FooNode(),
                    new BarNode(),
                    new FooNode(),
                ],
            ),
        );

        self::assertSame(self::PHP_PREFIX . '/* foo *//* bar *//* foo */', $output);
    }

    public function testHandlersFromMultipleProvidersAreMerged(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new FooProvider(),
                new BarProvider(),
            ],
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new BarNode(),
                    new FooNode(),
                ],
            ),
        );

        self::assertSame(self::PHP_PREFIX . '/* bar *//* foo */', $output);
    }

    public function testPostHandlerStagesAndAppendsAfterMainPass(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new BarProvider(),
                new PostFooProvider(),
            ],
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new BarNode(),
                    new FooNode(),
                ],
            ),
        );

        self::assertSame(self::PHP_PREFIX . '/* bar *//* post-foo */', $output);
    }

    public function testPostHandlerNotInvokedWhenMainPassProducesEmpty(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new PostFooProvider(),
            ],
        );

        $output = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new FooNode(),
                ],
            ),
        );

        self::assertSame('', $output);
    }

    public function testStagedPostHandlersAreResetBetweenCompileRuns(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new BarProvider(),
                new PostFooProvider(),
            ],
        );

        $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new BarNode(),
                    new FooNode(),
                ],
            ),
        );

        $secondOutput = $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new BarNode(),
                ],
            ),
        );

        self::assertSame(self::PHP_PREFIX . '/* bar */', $secondOutput);
    }

    public function testThrowsOnUnknownNodeClass(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders();

        self::expectException(CompilerException::class);

        $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new FooNode(),
                ],
            ),
        );
    }

    public function testThrowsOnNodeOutsideValidScope(): void
    {
        $compiler = Compiler::createWithoutDefaultProviders(
            providers: [
                new OutOfScopeProvider(),
            ],
        );

        self::expectException(CompilerException::class);

        $compiler->compile(
            stream: new NodeStream(
                nodes: [
                    new OutOfScopeNode(),
                ],
            ),
        );
    }

    public function testCompileExpressionDelegatesToExpressionCompiler(): void
    {
        $expressionCompiler = new RecordingExpressionCompiler();

        $compiler = Compiler::createWithoutDefaultProviders(
            expressionCompiler: $expressionCompiler,
        );

        $node = new LiteralNode(
            operand: '5',
            type: Type::INT,
        );

        $output = $compiler->compileExpression($node);

        self::assertSame('/* expr */', $output);
        self::assertSame($compiler, $expressionCompiler->lastCompiler);
        self::assertNotNull($expressionCompiler->lastStream);
        self::assertCount(1, $expressionCompiler->lastStream->nodes);
        self::assertSame($node, $expressionCompiler->lastStream->nodes[0]);
    }
}
