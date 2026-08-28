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

use Tuxxedo\Mail\MailException;

class SmtpSocket implements SmtpSocketInterface
{
    public bool $isConnected {
        get {
            return $this->stream !== null;
        }
    }

    /**
     * @var resource|null
     */
    private mixed $stream = null;

    private int $readTimeout = 0;

    public function connect(
        string $host,
        int $port,
        SmtpTls $tls,
        int $connectTimeout,
        int $readTimeout,
        bool $verifyPeer,
        ?string $caFile,
        ?string $unixSocket = null,
    ): void {
        if ($unixSocket !== null) {
            $address = 'unix://' . $unixSocket;
            $context = \stream_context_create();
        } else {
            $scheme = $tls === SmtpTls::IMPLICIT
                ? 'ssl'
                : 'tcp';

            $address = \sprintf(
                '%s://%s:%d',
                $scheme,
                $host,
                $port,
            );

            $context = \stream_context_create(
                options: [
                    'ssl' => self::buildSslOptions($verifyPeer, $caFile),
                ],
            );
        }

        $errno = 0;
        $errstr = '';
        $stream = \stream_socket_client(
            address: $address,
            error_code: $errno,
            error_message: $errstr,
            timeout: (float) $connectTimeout,
            flags: \STREAM_CLIENT_CONNECT,
            context: $context,
        );

        if ($stream === false) {
            $errstr ??= '';
            $errno ??= 0;

            throw MailException::fromSmtpConnectionFailure(
                host: $unixSocket ?? $host,
                port: $unixSocket !== null
                    ? 0
                    : $port,
                reason: $errstr !== ''
                    ? $errstr
                    : \sprintf('errno %d', $errno),
            );
        }

        \stream_set_timeout($stream, $readTimeout);

        $this->stream = $stream;
        $this->readTimeout = $readTimeout;
    }

    public function enableCrypto(): void
    {
        $stream = $this->requireStream();
        $result = \stream_socket_enable_crypto(
            stream: $stream,
            enable: true,
            crypto_method: \STREAM_CRYPTO_METHOD_TLS_CLIENT,
        );

        if ($result !== true) {
            throw MailException::fromSmtpTlsHandshakeFailed(
                reason: 'stream_socket_enable_crypto did not complete successfully',
            );
        }
    }

    public function writeCommand(
        string $command,
    ): void {
        $this->writeRaw($command . "\r\n");
    }

    public function writeRaw(
        string $bytes,
    ): void {
        $stream = $this->requireStream();
        $remaining = $bytes;

        while ($remaining !== '') {
            $written = \fwrite($stream, $remaining);

            if ($written === false || $written === 0) {
                throw MailException::fromSmtpWriteFailure();
            }

            $remaining = \substr($remaining, $written);
        }
    }

    #[\NoDiscard]
    public function readResponse(): SmtpResponse
    {
        $stream = $this->requireStream();
        $lines = [];
        $code = 0;

        while (true) {
            $line = \fgets($stream);

            if ($line === false) {
                $metadata = \stream_get_meta_data($stream);

                if ($metadata['timed_out']) {
                    throw MailException::fromSmtpReadTimeout($this->readTimeout);
                }

                throw MailException::fromSmtpReadFailure();
            }

            $trimmed = \rtrim($line, "\r\n");

            if (\strlen($trimmed) < 4) {
                throw MailException::fromSmtpMalformedResponse($trimmed);
            }

            $lineCode = (int) \substr($trimmed, 0, 3);
            $separator = $trimmed[3];
            $text = \substr($trimmed, 4);
            $lines[] = $text;

            if ($code === 0) {
                $code = $lineCode;
            }

            if ($separator === ' ') {
                break;
            }

            if ($separator !== '-') {
                throw MailException::fromSmtpMalformedResponse($trimmed);
            }
        }

        return new SmtpResponse(
            code: $code,
            lines: $lines,
        );
    }

    public function disconnect(): void
    {
        if ($this->stream === null) {
            return;
        }

        @\fclose($this->stream);

        $this->stream = null;
    }

    /**
     * @return resource
     *
     * @throws MailException
     */
    private function requireStream(): mixed
    {
        if ($this->stream === null) {
            throw MailException::fromSmtpNotConnected();
        }

        return $this->stream;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildSslOptions(
        bool $verifyPeer,
        ?string $caFile,
    ): array {
        $options = [
            'verify_peer' => $verifyPeer,
            'verify_peer_name' => $verifyPeer,
        ];

        if ($caFile !== null) {
            $options['cafile'] = $caFile;
        }

        return $options;
    }
}
