<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Compiler\Optimizer;

use Tuxxedo\View\Lumi\Node\CommentNode;
use Tuxxedo\View\Lumi\Node\DirectiveNodeInterface;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class DceCompilerOptimizer extends AbstractOptimizer
{
    protected function optimizer(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        $nodes = [];

        while (!$stream->eof()) {
            $optimizedNodes = $this->optimizeNode($stream->current());

            if (\sizeof($optimizedNodes) > 0) {
                \array_push($nodes, ...$optimizedNodes);
            }

            $stream->consume();
        }

        return new NodeStream(
            nodes: $nodes,
        );
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeNode(
        NodeInterface $node,
    ): array {
        return match (true) {
            $node instanceof DirectiveNodeInterface => parent::optimizeDirective($node),
            $node instanceof CommentNode => $this->optimizeComment($node),
            default => [
                $node,
            ],
        };
    }

    /**
     * @return NodeInterface[]
     */
    private function optimizeComment(
        CommentNode $node,
    ): array {
        if ($this->directives->asBool('lumi.compiler_strip_comments')) {
            return [];
        }

        return [
            $node,
        ];
    }
}
