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

namespace App\Controllers;

use App\Mail\OrderShippedMail;
use App\Mail\WelcomeMail;
use Tuxxedo\Http\Request\Middleware\Csrf;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/mail')]
readonly class MailController
{
    private const array MAIL_LANGUAGES = [
        'en-US',
        'pt-PT',
        'pt-BR',
    ];

    private const array MAIL_TIME_ZONES = [
        'UTC',
        'Europe/Copenhagen',
        'Europe/Lisbon',
        'America/Sao_Paulo',
        'America/New_York',
    ];

    public function __construct(
        private MailManagerInterface $mailManager,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route\Get(path: '/')]
    public function form(): ViewInterface
    {
        return new View(
            name: 'mail_form',
        );
    }

    #[Route\Post(path: '/')]
    #[Middleware(Csrf::class)]
    public function send(
        RequestInterface $request,
    ): ViewInterface {
        $to = $request->post->string('to');
        $subject = $request->post->string('subject');
        $body = $request->post->string('body');
        $returnPath = $request->post->string('returnPath');

        try {
            $this->mailManager->send(
                new Message(
                    from: 'demo@example.com',
                    to: $to,
                    subject: $subject,
                    body: $body,
                    returnPath: $returnPath !== '' ? $returnPath : null,
                ),
            );
        } catch (MailException $e) {
            return new View(
                name: 'mail_form',
                scope: [
                    'resultSuccess' => false,
                    'resultMessage' => $e->getMessage(),
                ],
            );
        }

        return new View(
            name: 'mail_form',
            scope: [
                'resultSuccess' => true,
                'resultMessage' => \sprintf(
                    'Message dispatched to %s',
                    $to,
                ),
            ],
        );
    }

    #[Route\Post(path: '/template')]
    #[Middleware(Csrf::class)]
    public function sendTemplate(
        RequestInterface $request,
    ): ViewInterface {
        $to = $request->post->string('to');
        $recipientName = $request->post->string('recipientName');

        try {
            $this->mailManager->send(
                new WelcomeMail(
                    to: $to,
                    recipientName: $recipientName !== ''
                        ? $recipientName
                        : 'friend',
                    activationUrl: 'https://example.com/activate/demo-token',
                ),
            );
        } catch (MailException $e) {
            return new View(
                name: 'mail_form',
                scope: [
                    'resultSuccess' => false,
                    'resultMessage' => $e->getMessage(),
                ],
            );
        }

        return new View(
            name: 'mail_form',
            scope: [
                'resultSuccess' => true,
                'resultMessage' => \sprintf(
                    'Welcome template dispatched to %s',
                    $to,
                ),
            ],
        );
    }

    #[Route\Post(path: '/localized')]
    #[Middleware(Csrf::class)]
    public function sendLocalized(
        RequestInterface $request,
    ): ViewInterface {
        $to = $request->post->string('to');
        $language = $request->post->string('language');
        $timeZone = $request->post->string('timeZone');
        $recipientName = $request->post->string('recipientName');

        if (!\in_array($language, self::MAIL_LANGUAGES, true)) {
            $language = self::MAIL_LANGUAGES[0];
        }

        if (!\in_array($timeZone, self::MAIL_TIME_ZONES, true)) {
            $timeZone = self::MAIL_TIME_ZONES[0];
        }

        try {
            $this->mailManager->send(
                new OrderShippedMail(
                    translator: $this->translator,
                    to: $to,
                    locale: Locale::parse($language),
                    timeZone: TimeZone::parse($timeZone),
                    recipientName: $recipientName !== ''
                        ? $recipientName
                        : 'friend',
                    orderNumber: '10427',
                    itemCount: $request->post->int('itemCount'),
                    total: 1234.5,
                    shippedAt: Instant::now(),
                    trackingUrl: 'https://example.com/track/10427',
                ),
            );
        } catch (MailException $e) {
            return new View(
                name: 'mail_form',
                scope: [
                    'resultSuccess' => false,
                    'resultMessage' => $e->getMessage(),
                ],
            );
        }

        return new View(
            name: 'mail_form',
            scope: [
                'resultSuccess' => true,
                'resultMessage' => \sprintf(
                    'Order shipped template (%s, %s) dispatched to %s',
                    $language,
                    $timeZone,
                    $to,
                ),
            ],
        );
    }
}
