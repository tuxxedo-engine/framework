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

namespace Tuxxedo\View\Lumi\Compiler\Provider;

use Tuxxedo\View\Lumi\Compiler\CompilerException;
use Tuxxedo\View\Lumi\Compiler\CompilerInterface;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;

class LayoutCompilerProvider implements CompilerProviderInterface
{
    private function compileBlock(
        BlockNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        $body = '';

        foreach ($node->body as $blockNode) {
            $body .= $compiler->compileNode($blockNode, $stream);
        }

        if ($this->isLayoutStream($stream)) {
            return \sprintf(
                '<?php $this->block(\'%s\', \'%s\'); ?>',
                $node->name,
                $this->escapeBlockQuote($body),
            );
        }

        return \sprintf(
            '<?php if ($this->hasBlock(\'%1$s\')) { eval($this->blockCode(\'%1$s\')); } else { ?>%2$s<?php } ?>',
            $node->name,
            $body,
        );
    }

    private function compileLayout(
        LayoutNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        return \sprintf(
            '<?php $this->layout(\'%s\'); ?>',
            $node->file,
        );
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: BlockNode::class,
            handler: $this->compileBlock(...),
        );

        yield new PostNodeCompilerHandler(
            nodeClassName: LayoutNode::class,
            handler: $this->compileLayout(...),
        );
    }

    private function isLayoutStream(
        NodeStreamInterface $stream,
    ): bool {
        foreach ($stream->nodes as $node) {
            if ($node instanceof LayoutNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws CompilerException
     */
    private function escapeBlockQuote(
        string $input,
    ): string {
        return \preg_replace('/\'/u', '\\\'', $input) ?? throw CompilerException::fromCannotEscapeQuote();
    }
}
