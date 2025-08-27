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
use Tuxxedo\View\Lumi\Node\BuiltinNodeKinds;
use Tuxxedo\View\Lumi\Node\CommentNode;
use Tuxxedo\View\Lumi\Node\DeclareNode;
use Tuxxedo\View\Lumi\Node\EchoNode;
use Tuxxedo\View\Lumi\Node\TextNode;
use Tuxxedo\View\Lumi\Parser\NodeStream;

class TextCompilerProvider implements CompilerProviderInterface
{
    private function compileText(
        TextNode $node,
        CompilerInterface $compiler,
    ): string {
        return $this->stripPhpOpeningTag($node->text);
    }

    private function compileComment(
        CommentNode $node,
        CompilerInterface $compiler,
    ): string {
        $commentary = '';
        $lines = \preg_split('/\n/u', $node->text);

        if ($lines !== false) {
            foreach ($lines as $line) {
                $commentary .= \sprintf(
                    "<?php // %s ?>\n",
                    $this->stripPhpEndingTag(\mb_trim($line)),
                );
            }
        }

        return $commentary;
    }

    private function compileEcho(
        EchoNode $node,
        CompilerInterface $compiler,
    ): string {
        return \sprintf(
            '<?= %s; ?>',
            $compiler->expressionCompiler->compile(
                stream: new NodeStream(
                    nodes: [
                        $node->operand,
                    ],
                ),
                compiler: $compiler,
            ),
        );
    }

    private function compileDeclare(
        DeclareNode $node,
        CompilerInterface $compiler,
    ): string {
        $oldState = $compiler->state->swap(BuiltinNodeKinds::EXPRESSION->name);

        $output = \sprintf(
            '<?php $this->directive(%s, %s); ?>',
            $compiler->compileNode($node->directive),
            $compiler->compileNode($node->value),
        );

        $compiler->state->swap($oldState);

        return $output;
    }

    public function augment(): \Generator
    {
        yield new NodeCompilerHandler(
            nodeClassName: TextNode::class,
            handler: $this->compileText(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: CommentNode::class,
            handler: $this->compileComment(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: EchoNode::class,
            handler: $this->compileEcho(...),
        );

        yield new NodeCompilerHandler(
            nodeClassName: DeclareNode::class,
            handler: $this->compileDeclare(...),
        );
    }

    private function stripPhpOpeningTag(string $code): string
    {
        return \preg_replace('/\s*<\?\s*/ui', '&lt;?', $code) ?? throw CompilerException::fromCannotEscapePhp();
    }

    private function stripPhpEndingTag(string $code): string
    {
        return \preg_replace('/\s*\?>\s*$/u', '', $code) ?? throw CompilerException::fromCannotEscapePhp();
    }
}
