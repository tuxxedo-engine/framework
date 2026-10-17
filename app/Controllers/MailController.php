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
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Request\Middleware\Csrf;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\Mail\Config\MailManagerConfigInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\TemporalException;
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

    public function __construct(
        private MailManagerInterface $mailManager,
        private TranslatorInterface $translator,
        private MailManagerConfigInterface $mailConfig,
        private ContainerInterface $container,
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
        $mail = $this->orderShippedMail($request);

        try {
            $this->mailManager->send($mail);
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
                    $mail->locale->tag,
                    $mail->timeZone->name,
                    $request->post->string('to'),
                ),
            ],
        );
    }

    #[Route\Post(path: '/localized/preview')]
    #[Middleware(Csrf::class)]
    public function previewLocalized(
        RequestInterface $request,
    ): ResponseInterface {
        $render = $this->mailConfig->template?->createTemplateRender($this->container);

        if ($render === null) {
            return Response::text(
                text: 'No mail template renderer is configured',
                responseCode: ResponseCode::INTERNAL_SERVER_ERROR,
            );
        }

        return Response::html(
            html: $render->render($this->orderShippedMail($request))->body ?? '',
        );
    }

    private function orderShippedMail(
        RequestInterface $request,
    ): OrderShippedMail {
        $language = $request->post->string('language');
        $recipientName = $request->post->string('recipientName');

        if (!\in_array($language, self::MAIL_LANGUAGES, true)) {
            $language = self::MAIL_LANGUAGES[0];
        }

        try {
            $timeZone = TimeZone::parse($request->post->string('timeZone'));
        } catch (TemporalException) {
            $timeZone = TimeZone::utc();
        }

        return new OrderShippedMail(
            translator: $this->translator,
            to: $request->post->string('to'),
            locale: Locale::parse($language),
            timeZone: $timeZone,
            recipientName: $recipientName !== ''
                ? $recipientName
                : 'friend',
            orderNumber: '10427',
            itemCount: $request->post->int('itemCount'),
            total: 1234.5,
            currency: 'EUR',
            shippedAt: Instant::now()->minus(Duration::fromHours(3)),
            trackingUrl: 'https://example.com/track/10427',
        );
    }
}
