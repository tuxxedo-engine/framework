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

namespace Tuxxedo\Mail\Transport\SmtpMail;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;
use Tuxxedo\Mail\Transport\SmtpMail\Config\SmtpTransportConfigInterface;

class SmtpTransport implements MailTransportInterface
{
    public function __construct(
        private readonly SmtpTransportConfigInterface $config,
        private readonly SmtpSocketInterface $socket,
    ) {
    }

    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        if ($serialized === []) {
            return;
        }

        match ($this->config->mode) {
            SmtpTransportMode::PER_MESSAGE => $this->sendPerMessage($serialized),
            SmtpTransportMode::REUSE_CONNECTION => $this->sendReusingConnection($serialized),
            SmtpTransportMode::REUSE_UP_TO_N => $this->sendReusingUpToN(
                serialized: $serialized,
                reuseLimit: $this->config->reuseLimit,
            ),
        };
    }

    /**
     * @param array<int|string, SerializedMessageInterface> $serialized
     *
     * @throws MailException
     */
    private function sendPerMessage(
        array $serialized,
    ): void {
        foreach ($serialized as $item) {
            $session = $this->openSession();

            try {
                $this->dispatch(
                    session: $session,
                    serialized: $item,
                );
            } finally {
                $session->close();
            }
        }
    }

    /**
     * @param array<int|string, SerializedMessageInterface> $serialized
     *
     * @throws MailException
     */
    private function sendReusingConnection(
        array $serialized,
    ): void {
        $session = $this->openSession();

        try {
            $first = true;

            foreach ($serialized as $item) {
                if (!$first) {
                    $session->reset();
                }

                $this->dispatch(
                    session: $session,
                    serialized: $item,
                );

                $first = false;
            }
        } finally {
            $session->close();
        }
    }

    /**
     * @param array<int|string, SerializedMessageInterface> $serialized
     *
     * @throws MailException
     */
    private function sendReusingUpToN(
        array $serialized,
        int $reuseLimit,
    ): void {
        if ($reuseLimit <= 0) {
            $this->sendReusingConnection($serialized);

            return;
        }

        foreach (\array_chunk($serialized, $reuseLimit) as $chunk) {
            $this->sendReusingConnection($chunk);
        }
    }

    /**
     * @throws MailException
     */
    private function openSession(): SmtpSession
    {
        $session = new SmtpSession(
            socket: $this->socket,
        );

        $session->open(
            host: $this->config->host,
            port: $this->config->port,
            tls: $this->config->tls,
            connectTimeout: $this->config->connectTimeout,
            readTimeout: $this->config->readTimeout,
            verifyPeer: $this->config->verifyPeer,
            caFile: $this->config->caFile,
            ehloDomain: $this->config->ehloDomain ?? self::resolveDefaultEhloDomain(),
        );

        $session->authenticate(
            mechanism: $this->config->auth,
            username: $this->config->username,
            password: $this->config->password,
            xoauthTokenProvider: $this->config->xoauthTokenProvider,
        );

        return $session;
    }

    /**
     * @throws MailException
     */
    private function dispatch(
        SmtpSession $session,
        SerializedMessageInterface $serialized,
    ): void {
        $message = $serialized->source;

        $session->sendMessage(
            serialized: $serialized,
            envelopeFrom: $message->returnPath ?? $message->from,
            recipients: self::collectRecipients($message),
        );
    }

    /**
     * @return list<AddressInterface>
     */
    private static function collectRecipients(
        MessageInterface $message,
    ): array {
        $recipients = [];

        foreach ($message->to as $recipient) {
            $recipients[] = $recipient;
        }

        foreach ($message->cc as $recipient) {
            $recipients[] = $recipient;
        }

        foreach ($message->bcc as $recipient) {
            $recipients[] = $recipient;
        }

        return $recipients;
    }

    private static function resolveDefaultEhloDomain(): string
    {
        $hostname = \gethostname();

        return $hostname !== false
            ? $hostname
            : 'localhost';
    }
}
