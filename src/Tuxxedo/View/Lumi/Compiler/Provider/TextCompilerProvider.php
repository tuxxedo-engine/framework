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
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Node\BuiltinNodeScopes;
use Tuxxedo\View\Lumi\Syntax\Node\CommentNode;
use Tuxxedo\View\Lumi\Syntax\Node\DeclareNode;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Node\LayoutNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;

class TextCompilerProvider implements CompilerProviderInterface
{
    private function compileText(
        TextNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if (
            $this->isLayoutStream($stream) &&
            !$compiler->state->is(BuiltinNodeScopes::BLOCK->name)
        ) {
            return '';
        }

        return $this->stripPhpOpeningTag($node->text);
    }

    private function compileComment(
        CommentNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        if ($compiler->state->directives->asBool('lumi.compiler_strip_comments')) {
            return '';
        }

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
        NodeStreamInterface $stream,
    ): string {
        $value = $compiler->compileExpression($node->operand);

        $autoEscape = $compiler->state->directives->asBool('lumi.autoescape');

        if (
            (
                $node->operand instanceof LiteralNode &&
                $node->operand->type !== NativeType::STRING
            ) ||
            (
                $node->operand instanceof BinaryOpNode &&
                $node->operand->left instanceof LiteralNode &&
                $node->operand->left->type !== NativeType::STRING &&
                $node->operand->right instanceof LiteralNode &&
                $node->operand->right->type !== NativeType::STRING
            ) ||
            (
                $node->operand instanceof UnaryOpNode &&
                $node->operand->operand instanceof LiteralNode &&
                $node->operand->operand->type !== NativeType::STRING
            )
        ) {
            $autoEscape = false;
        }

        if ($autoEscape) {
            return \sprintf(
                '<?= $this->filter(%s, \'escape_html\'); ?>',
                $value,
            );
        }

        return \sprintf(
            '<?= %s; ?>',
            $value,
        );
    }

    private function compileDeclare(
        DeclareNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        $oldState = $compiler->state->swap(BuiltinNodeScopes::EXPRESSION->name);

        $compiler->state->directives->set(
            $node->directive->operand,
            match ($node->value->type) {
                NativeType::STRING => $node->value->operand,
                NativeType::INT => \intval($node->value->operand),
                NativeType::FLOAT => \floatval($node->value->operand),
                NativeType::BOOL => $node->value->operand === 'true',
                NativeType::NULL => null,
            },
        );

        $output = \sprintf(
            '<?php $this->directive(%s, %s); ?>',
            $compiler->compileNode($node->directive, $stream),
            $compiler->compileNode($node->value, $stream),
        );

        $compiler->state->swap($oldState);

        return $output;
    }

    private function compileBlock(
        BlockNode $node,
        CompilerInterface $compiler,
        NodeStreamInterface $stream,
    ): string {
        $body = '';

        $oldState = $compiler->state->swap(BuiltinNodeScopes::BLOCK->name);

        foreach ($node->body as $blockNode) {
            $body .= $compiler->compileNode($blockNode, $stream);
        }

        $compiler->state->swap($oldState);

        if ($this->isLayoutStream($stream)) {
            return \sprintf(
                "\n<?php \$this->block('%s', '%s'); ?>",
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
            "\n<?php \$this->layout('%s'); ?>",
            $node->file,
        );
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

        yield new NodeCompilerHandler(
            nodeClassName: BlockNode::class,
            handler: $this->compileBlock(...),
        );

        yield new PostNodeCompilerHandler(
            nodeClassName: LayoutNode::class,
            handler: $this->compileLayout(...),
        );
    }

    /**
     * @throws CompilerException
     */
    private function stripPhpOpeningTag(string $code): string
    {
        return \preg_replace('/\s*<\?\s*/ui', '&lt;?', $code) ?? throw CompilerException::fromCannotEscapePhp();
    }

    /**
     * @throws CompilerException
     */
    private function stripPhpEndingTag(string $code): string
    {
        return \preg_replace('/\s*\?>\s*$/u', '', $code) ?? throw CompilerException::fromCannotEscapePhp();
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
