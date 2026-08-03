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

namespace Tuxxedo\Mail\Middleware;

use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\MessageInterface;

class ListUnsubscribeMiddleware implements MailMiddlewareInterface
{
    /**
     * @param \Closure(MessageInterface): string $unsubscribeUrlBuilder
     */
    public function __construct(
        private readonly \Closure $unsubscribeUrlBuilder,
        private readonly bool $oneClick = true,
    ) {
    }

    public function process(
        MessageInterface $message,
    ): MessageInterface {
        $url = ($this->unsubscribeUrlBuilder)($message);

        $result = $message->withExtraHeader(
            new Header(
                name: 'List-Unsubscribe',
                value: '<' . $url . '>',
            ),
        );

        if (!$this->oneClick) {
            return $result;
        }

        return $result->withExtraHeader(
            new Header(
                name: 'List-Unsubscribe-Post',
                value: 'List-Unsubscribe=One-Click',
            ),
        );
    }
}
