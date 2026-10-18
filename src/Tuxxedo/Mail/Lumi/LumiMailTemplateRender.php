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

namespace Tuxxedo\Mail\Lumi;

use Tuxxedo\Mail\Attribute\MailTemplate;
use Tuxxedo\Mail\CurrencyDenominatedMessageInterface;
use Tuxxedo\Mail\LocalizedMessageInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailTemplateRenderInterface;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\TimeZonedMessageInterface;
use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;

class LumiMailTemplateRender implements MailTemplateRenderInterface
{
    public function __construct(
        public readonly LumiViewRenderInterface $viewRender,
    ) {
    }

    public function render(
        MessageInterface $message,
    ): MessageInterface {
        $template = MailTemplate::extractFrom($message);

        if ($template === null) {
            throw MailException::fromMissingMailTemplateAttribute(
                className: $message::class,
            );
        }

        try {
            /** @var array<string, mixed> $scope */
            $scope = \get_object_vars($message);

            $body = $this->viewRender->render(
                view: new View(
                    name: $template->name,
                    scope: $scope,
                ),
                directives: $this->directivesFor($message),
            );
        } catch (ViewException $exception) {
            throw MailException::fromMailTemplateRenderFailed(
                className: $message::class,
                templateName: $template->name,
                previous: $exception,
            );
        }

        return $message->withBody(
            body: $body,
            bodyType: $template->bodyType,
        );
    }

    /**
     * @return array<string, string|int|float|bool|null>|null
     */
    private function directivesFor(
        MessageInterface $message,
    ): ?array {
        $directives = [];

        if ($message instanceof LocalizedMessageInterface) {
            $directives['tuxxedo.locale'] = $message->locale->tag;
        }

        if ($message instanceof TimeZonedMessageInterface) {
            $directives['tuxxedo.timezone'] = $message->timeZone->name;
        }

        if ($message instanceof CurrencyDenominatedMessageInterface) {
            $directives['tuxxedo.currency'] = $message->currency;
        }

        if ($directives === []) {
            return null;
        }

        return [
            ...$this->viewRender->runtime->directives,
            ...$directives,
        ];
    }
}
