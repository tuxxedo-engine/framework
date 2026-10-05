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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\Mail\AttachmentInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class MailFunctions
{
    public function __construct(
        private readonly EscaperInterface $escaper = new Escaper(),
    ) {
    }

    /**
     * @param iterable<mixed> $attachments
     */
    #[LumiFunction('has_attachment')]
    public function hasAttachment(
        iterable $attachments,
        string $name,
    ): bool {
        $expected = '<' . $name . '>';

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof AttachmentInterface) {
                continue;
            }

            if ($attachment->contentId === $expected) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param iterable<mixed> $attachments
     *
     * @throws RuntimeException
     */
    #[LumiFunction('inline_image')]
    public function inlineImage(
        iterable $attachments,
        string $name,
        string $alt = '',
    ): string {
        $expected = '<' . $name . '>';

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof AttachmentInterface) {
                continue;
            }

            if ($attachment->contentId !== $expected) {
                continue;
            }

            return \sprintf(
                '<img src="cid:%s" alt="%s">',
                $this->escaper->attribute($name),
                $this->escaper->attribute($alt),
            );
        }

        throw RuntimeException::fromMissingInlineAttachment(
            name: $name,
        );
    }
}
