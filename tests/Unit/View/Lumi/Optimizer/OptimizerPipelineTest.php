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

namespace Unit\View\Lumi\Optimizer;

use Fixture\View\Lumi\RecordingOptimizer;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\OptimizerPipeline;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;

class OptimizerPipelineTest extends TestCase
{
    public function testEmptyPipelineReturnsStreamUnchanged(): void
    {
        $pipeline = new OptimizerPipeline();
        $stream = new NodeStream(
            nodes: [
                new TextNode(
                    text: 'hello',
                ),
            ],
        );

        self::assertSame($stream, $pipeline->run($stream));
    }

    public function testSingleOptimizerRunsAtLeastOnce(): void
    {
        $optimizer = new RecordingOptimizer();
        $pipeline = new OptimizerPipeline(
            optimizers: [
                $optimizer,
            ],
        );

        $pipeline->run(
            stream: new NodeStream(
                nodes: [
                    new TextNode(
                        text: 'x',
                    ),
                ],
            ),
        );

        self::assertSame(1, $optimizer->callCount);
    }

    public function testPipelineLoopsUntilFixpoint(): void
    {
        $optimizer = new RecordingOptimizer(
            changeCount: 3,
        );
        $pipeline = new OptimizerPipeline(
            optimizers: [
                $optimizer,
            ],
        );

        $pipeline->run(
            stream: new NodeStream(
                nodes: [
                    new TextNode(
                        text: 'x',
                    ),
                ],
            ),
        );

        self::assertSame(4, $optimizer->callCount);
    }

    public function testMultipleOptimizersRunInDeclarationOrder(): void
    {
        $first = new RecordingOptimizer();
        $second = new RecordingOptimizer();
        $pipeline = new OptimizerPipeline(
            optimizers: [
                $first,
                $second,
            ],
        );

        $pipeline->run(
            stream: new NodeStream(
                nodes: [
                    new TextNode(
                        text: 'x',
                    ),
                ],
            ),
        );

        self::assertSame(1, $first->callCount);
        self::assertSame(1, $second->callCount);
    }

    public function testPipelineReturnsFinalStreamAfterMutation(): void
    {
        $optimizer = new RecordingOptimizer(
            changeCount: 1,
        );
        $pipeline = new OptimizerPipeline(
            optimizers: [
                $optimizer,
            ],
        );

        $result = $pipeline->run(
            stream: new NodeStream(
                nodes: [
                    new TextNode(
                        text: 'original',
                    ),
                ],
            ),
        );

        self::assertCount(1, $result->nodes);
        self::assertInstanceOf(TextNode::class, $result->nodes[0]);
        self::assertSame('mutated-1', $result->nodes[0]->text);
    }

    public function testOptimizersFieldExposesConfiguredList(): void
    {
        $optimizers = [
            new RecordingOptimizer(),
            new RecordingOptimizer(),
        ];

        $pipeline = new OptimizerPipeline(
            optimizers: $optimizers,
        );

        self::assertSame($optimizers, $pipeline->optimizers);
    }
}
