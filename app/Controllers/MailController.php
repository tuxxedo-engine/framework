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

use App\Mail\WelcomeMail;
use Tuxxedo\Http\Request\Middleware\Csrf;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Middleware;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/mail')]
readonly class MailController
{
    public function __construct(
        private MailManagerInterface $mailManager,
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
}
