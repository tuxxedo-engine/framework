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

namespace Tuxxedo\Mail\Transport\SendMail;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;
use Tuxxedo\Mail\Transport\SendMail\Config\SendMailTransportConfigInterface;
use Tuxxedo\Process\ProcessCommand;
use Tuxxedo\Process\ProcessException;
use Tuxxedo\Process\ProcessRunnerInterface;

class SendMailTransport implements MailTransportInterface
{
    public function __construct(
        private readonly SendMailTransportConfigInterface $config,
        private readonly ProcessRunnerInterface $processRunner,
    ) {
    }

    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        foreach ($serialized as $item) {
            $this->deliver($item);
        }
    }

    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array {
        $results = [];

        foreach ($serialized as $item) {
            $message = $item->source;
            $recipients = self::collectRecipients($message);

            try {
                $this->deliver($item);

                $status = RecipientStatus::ACCEPTED;
                $summary = null;
            } catch (MailException $e) {
                $status = RecipientStatus::PERMANENT_FAILURE;
                $summary = $e->getMessage();
            }

            $outcomes = [];

            foreach ($recipients as $recipient) {
                $outcomes[] = new RecipientOutcome(
                    recipient: $recipient,
                    status: $status,
                    summary: $summary,
                );
            }

            $results[] = new SendResult(
                message: $message,
                outcomes: $outcomes,
            );
        }

        return $results;
    }

    /**
     * @throws MailException
     */
    private function deliver(
        SerializedMessageInterface $serialized,
    ): void {
        $message = $serialized->source;
        $envelopeFrom = ($message->returnPath ?? $message->from)->email;

        $arguments = [
            ...$this->config->arguments,
            '-f',
            $envelopeFrom,
        ];

        $command = new ProcessCommand(
            binary: $this->config->binary,
            arguments: $arguments,
            stdin: $serialized->wire,
            timeoutSeconds: $this->config->timeoutSeconds,
        );

        try {
            $result = $this->processRunner->run($command);
        } catch (ProcessException $exception) {
            throw MailException::fromSendMailFailure(
                exitCode: -1,
                stderr: $exception->getMessage(),
            );
        }

        if (!$result->isSuccess) {
            throw MailException::fromSendMailFailure(
                exitCode: $result->exitCode,
                stderr: $result->stderr,
            );
        }
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
}
