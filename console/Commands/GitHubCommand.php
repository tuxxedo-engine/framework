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

namespace Console\Commands;

use Console\GitHub\Commit;
use Console\GitHub\GitHubClient;
use Console\GitHub\Issue;
use Console\GitHub\PullRequest;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\Attribute\Option;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Output\Color;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\ProgressBar;
use Tuxxedo\Console\Output\ProgressBarTheme;
use Tuxxedo\Console\Output\Style\Decoration;
use Tuxxedo\Console\Output\Style\Style;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Temporal\InstantInterface;

class GitHubCommand
{
    private const int LIMIT = 5;

    #[Command('gh', description: 'Show the latest issues, pull requests and commits of a GitHub repository')]
    public function show(
        OutputInterface $output,
        HttpClientInterface $http,
        EnvInterface $env,
        #[Option(description: 'Repository as owner/name')]
        ?string $repo = null,
    ): ExitCode {
        $repo ??= $env->string('GITHUB_REPOSITORY', 'tuxxedo-engine/framework');

        $github = new GitHubClient(
            http: $http,
            repository: $repo,
            token: $env->string('GITHUB_TOKEN', ''),
        );

        $bar = new ProgressBar(
            output: $output,
            total: 3 + self::LIMIT,
            theme: new ProgressBarTheme(
                width: 30,
                filledSegment: '█',
                emptySegment: '░',
                head: '',
                leadingCap: '[',
                trailingCap: ']',
                filledStyle: Style::success(),
                emptyStyle: Style::dim(),
                capStyle: new Style(
                    foreground: Color::LIGHT_BLUE,
                ),
                percentageStyle: new Style(
                    foreground: Color::LIGHT_YELLOW,
                    decorations: [
                        Decoration::BOLD,
                    ],
                ),
            ),
        );

        $issues = $github->issues();
        $pullRequests = $github->pullRequests();
        $commitList = $github->commits();

        try {
            foreach (
                $github->each(
                    [
                        $issues,
                        $pullRequests,
                        $commitList,
                    ],
                ) as $_
            ) {
                $bar->advance();
            }

            $details = [];

            foreach ($commitList->result() as $commit) {
                $details[] = $github->commit($commit->sha);
            }

            foreach ($github->each($details) as $_) {
                $bar->advance();
            }

            $bar->finish();
            $output->line();

            $this->issues($output, $repo, $issues->result());
            $this->pullRequests($output, $pullRequests->result());

            $commits = [];

            foreach ($details as $detail) {
                $commits[] = $detail->result();
            }

            $this->commits($output, $commits);
        } catch (HttpClientException $exception) {
            $bar->finish();
            $output->line();
            $output->error($exception->getMessage());
            $output->line();

            return ExitCode::FAILURE;
        }

        return ExitCode::SUCCESS;
    }

    /**
     * @param list<Issue> $issues
     */
    private function issues(
        OutputInterface $output,
        string $repo,
        array $issues,
    ): void {
        $output->line();
        $output->styled(
            bytes: $repo,
            style: new Style(
                foreground: Color::LIGHT_MAGENTA,
                decorations: [
                    Decoration::BOLD,
                ],
            ),
        );

        $output->line();
        $output->line();

        $this->header($output, 'Open issues');

        $shown = 0;

        foreach ($issues as $issue) {
            if ($issue->pullRequest !== null || $shown === self::LIMIT) {
                continue;
            }

            $this->item($output, $issue->number, $issue->title, $issue->user->login, $issue->createdAt);

            $shown++;
        }

        if ($shown === 0) {
            $this->none($output);
        }

        $output->line();
    }

    /**
     * @param list<PullRequest> $pullRequests
     */
    private function pullRequests(
        OutputInterface $output,
        array $pullRequests,
    ): void {
        $this->header($output, 'Open pull requests');

        foreach ($pullRequests as $pullRequest) {
            $this->item(
                $output,
                $pullRequest->number,
                $pullRequest->title,
                $pullRequest->user->login,
                $pullRequest->createdAt,
                $pullRequest->draft,
            );
        }

        if (\sizeof($pullRequests) === 0) {
            $this->none($output);
        }

        $output->line();
    }

    /**
     * @param list<Commit> $commits
     */
    private function commits(
        OutputInterface $output,
        array $commits,
    ): void {
        $this->header($output, 'Latest commits');

        foreach ($commits as $commit) {
            $output->write('[');
            $output->write($commit->author->login ?? $commit->commit->author->name, Color::LIGHT_CYAN);
            $output->write('] <');
            $output->styled($commit->commit->author->date->format('Y-m-d H:i'), Style::dim());
            $output->write(', ');
            $output->write(\substr($commit->sha, 0, 7), Color::LIGHT_YELLOW);
            $output->write(', ');
            $output->write('+' . ($commit->stats->additions ?? 0), Color::LIGHT_GREEN);
            $output->write('/');
            $output->write('-' . ($commit->stats->deletions ?? 0), Color::LIGHT_RED);
            $output->write('> ');
            $output->line(\explode("\n", $commit->commit->message, 2)[0]);
        }

        if (\sizeof($commits) === 0) {
            $this->none($output);
        }

        $output->line();
    }

    private function item(
        OutputInterface $output,
        int $number,
        string $title,
        string $author,
        InstantInterface $createdAt,
        bool $draft = false,
    ): void {
        $output->write('#' . $number, Color::LIGHT_YELLOW);
        $output->write(' ');

        if ($draft) {
            $output->styled('[draft] ', Style::dim());
        }

        $output->write($title);
        $output->write(' ');
        $output->styled('(' . $author . ', ' . $createdAt->format('Y-m-d H:i') . ')', Style::dim());
        $output->line();
    }

    private function header(
        OutputInterface $output,
        string $title,
    ): void {
        $output->styled(
            bytes: '── ' . $title . ' ──',
            style: new Style(
                foreground: Color::LIGHT_WHITE,
                decorations: [
                    Decoration::BOLD,
                ],
            ),
        );

        $output->line();
    }

    private function none(
        OutputInterface $output,
    ): void {
        $output->styled('none', Style::dim());
        $output->line();
    }
}
