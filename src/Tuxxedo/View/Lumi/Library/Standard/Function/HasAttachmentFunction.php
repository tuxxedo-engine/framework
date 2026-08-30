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

use Tuxxedo\Mail\AttachmentInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class HasAttachmentFunction implements FunctionInterface
{
    public private(set) string $name = 'has_attachment';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): bool {
        /** @var iterable<mixed> $attachments */
        $attachments = $arguments[0];

        /** @var string $name */
        $name = $arguments[1];

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
}
