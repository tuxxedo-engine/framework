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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\Mail\AttachmentInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class InlineImageFunction implements FunctionInterface
{
    public private(set) string $name = 'inline_image';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param \Closure(): RuntimeContextInterface $context
     *
     * @throws RuntimeException
     */
    public function call(
        array $arguments,
        \Closure $context,
    ): string {
        /** @var iterable<mixed> $attachments */
        $attachments = $arguments[0];

        /** @var string $name */
        $name = $arguments[1];

        /** @var string $alt */
        $alt = $arguments[2] ?? '';

        $expected = '<' . $name . '>';

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof AttachmentInterface) {
                continue;
            }

            if ($attachment->contentId !== $expected) {
                continue;
            }

            $escaper = $this->container->resolve(EscaperInterface::class);

            return \sprintf(
                '<img src="cid:%s" alt="%s">',
                $escaper->attribute($name),
                $escaper->attribute($alt),
            );
        }

        throw RuntimeException::fromMissingInlineAttachment(
            name: $name,
        );
    }
}
