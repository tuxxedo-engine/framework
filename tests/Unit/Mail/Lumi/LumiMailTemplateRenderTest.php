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

namespace Unit\Mail\Lumi;

use PHPUnit\Framework\TestCase;
use Support\Mail\StubTemplateMessage;
use Tuxxedo\Container\Container;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Lumi\Config\LumiMailTemplateConfig;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MailTemplateRenderInterface;
use Tuxxedo\Mail\Message;

class LumiMailTemplateRenderTest extends TestCase
{
    private string $templatesDir;
    private string $cacheDir;
    private int $initialBufferLevel = 0;

    /**
     * @var list<string>
     */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->initialBufferLevel = \ob_get_level();
        $this->templatesDir = $this->createTempDirectory('templates');
        $this->cacheDir = $this->createTempDirectory('cache');
    }

    protected function tearDown(): void
    {
        while (\ob_get_level() > $this->initialBufferLevel) {
            \ob_end_clean();
        }

        foreach (\array_reverse($this->tempPaths) as $path) {
            $this->removeRecursive($path);
        }

        $this->tempPaths = [];
    }

    private function createTempDirectory(
        string $prefix,
    ): string {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo_mail_template_' . $prefix . '_');

        if ($path === false) {
            throw new \RuntimeException('Failed to allocate a temporary path');
        }

        \unlink($path);
        \mkdir($path, 0755, true);

        $normalized = \str_replace('\\', '/', $path);
        $this->tempPaths[] = $normalized;

        return $normalized;
    }

    private function removeRecursive(
        string $path,
    ): void {
        if (!\is_dir($path)) {
            if (\is_file($path)) {
                @\unlink($path);
            }

            return;
        }

        $entries = \glob($path . '/*');

        if (\is_array($entries)) {
            foreach ($entries as $entry) {
                $this->removeRecursive($entry);
            }
        }

        @\rmdir($path);
    }

    private function writeTemplate(
        string $name,
        string $contents,
    ): void {
        \file_put_contents(
            $this->templatesDir . '/' . $name . '.lumi',
            $contents,
        );
    }

    private function createRender(): MailTemplateRenderInterface
    {
        return (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            alwaysCompile: true,
        ))->createTemplateRender(
            container: new Container(),
        );
    }

    public function testRenderSetsRenderedBodyOnReturnedMessage(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: 'Hi {{ userName }}!',
        );

        $render = $this->createRender();

        $result = $render->render(
            message: new StubTemplateMessage(
                userName: 'kalle',
            ),
        );

        self::assertSame(
            'Hi kalle!',
            $result->body,
        );
    }

    public function testRenderSetsBodyTypeFromAttribute(): void
    {
        $this->writeTemplate(
            name: 'stub',
            contents: 'body',
        );

        $render = $this->createRender();

        $result = $render->render(
            message: new StubTemplateMessage(),
        );

        self::assertSame(
            BodyType::HTML,
            $result->bodyType,
        );
    }

    public function testRenderThrowsWhenMessageHasNoMailTemplateAttribute(): void
    {
        $render = $this->createRender();

        try {
            $render->render(
                message: new Message(
                    from: new Address(
                        email: 'from@example.com',
                    ),
                    to: [
                        new Address(
                            email: 'to@example.com',
                        ),
                    ],
                    subject: 'Plain',
                ),
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                Message::class,
                $exception->getMessage(),
            );
        }
    }

    public function testRenderWrapsUnderlyingViewExceptionAsMailException(): void
    {
        $render = $this->createRender();

        try {
            $render->render(
                message: new StubTemplateMessage(),
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                'stub',
                $exception->getMessage(),
            );
        }
    }

    public function testConfigWithAlwaysCompileDisabledStillProducesRender(): void
    {
        $render = (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            alwaysCompile: false,
        ))->createTemplateRender(
            container: new Container(),
        );

        self::assertInstanceOf(
            MailTemplateRenderInterface::class,
            $render,
        );
    }

    public function testConfigWithErrorReportingEnabledStillProducesRender(): void
    {
        $render = (new LumiMailTemplateConfig(
            directory: $this->templatesDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
            disableErrorReporting: false,
        ))->createTemplateRender(
            container: new Container(),
        );

        self::assertInstanceOf(
            MailTemplateRenderInterface::class,
            $render,
        );
    }
}
