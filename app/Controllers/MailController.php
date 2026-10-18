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

use App\Input\OrderShippedMailInput;
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
use Tuxxedo\Temporal\TimeZone;
use Tuxxedo\Validator\Message\MessageFormatterInterface;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/mail')]
readonly class MailController
{
    public function __construct(
        private MailManagerInterface $mailManager,
        private TranslatorInterface $translator,
        private MailManagerConfigInterface $mailConfig,
        private ContainerInterface $container,
        private ValidatorInterface $validator,
        private MessageFormatterInterface $messageFormatter,
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
        try {
            $input = $this->orderShippedInput($request);

            $this->mailManager->send(
                $this->orderShippedMail($input),
            );
        } catch (ValidationException $e) {
            return new View(
                name: 'mail_form',
                scope: [
                    'resultSuccess' => false,
                    'resultMessage' => \join(' ', $this->formatViolations($e)),
                ],
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
                    'Order shipped template (%s, %s, %s) dispatched to %s',
                    $input->language,
                    $input->timeZone,
                    $input->currency,
                    $input->to,
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

        try {
            $input = $this->orderShippedInput($request);
        } catch (ValidationException $e) {
            return Response::text(
                text: \join(\PHP_EOL, $this->formatViolations($e)),
                responseCode: ResponseCode::UNPROCESSABLE_ENTITY,
            );
        }

        return Response::html(
            html: $render->render($this->orderShippedMail($input))->body ?? '',
        );
    }

    /**
     * @throws ValidationException
     */
    private function orderShippedInput(
        RequestInterface $request,
    ): OrderShippedMailInput {
        $input = new OrderShippedMailInput(
            to: $request->post->string('to'),
            recipientName: $request->post->string('recipientName'),
            language: $request->post->string('language'),
            timeZone: $request->post->string('timeZone'),
            currency: $request->post->string('currency'),
            itemCount: $request->post->int('itemCount'),
        );

        $this->validator->validateOrThrow($input);

        return $input;
    }

    private function orderShippedMail(
        OrderShippedMailInput $input,
    ): OrderShippedMail {
        return new OrderShippedMail(
            translator: $this->translator,
            to: $input->to,
            locale: Locale::parse($input->language),
            timeZone: TimeZone::parse($input->timeZone),
            recipientName: $input->recipientName !== ''
                ? $input->recipientName
                : 'friend',
            orderNumber: '10427',
            itemCount: $input->itemCount,
            total: 1234.5,
            currency: \strtoupper($input->currency),
            shippedAt: Instant::now()->minus(Duration::fromHours(3)),
            trackingUrl: 'https://example.com/track/10427',
        );
    }

    /**
     * @return list<string>
     */
    private function formatViolations(
        ValidationException $exception,
    ): array {
        $errors = [];

        foreach ($exception->violations as $violation) {
            $errors[] = $this->messageFormatter->format($violation);
        }

        return $errors;
    }
}
