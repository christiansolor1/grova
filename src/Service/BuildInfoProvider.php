<?php

namespace App\Service;

final class BuildInfoProvider
{
    private ?array $cached = null;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $baseVersion,
        private readonly string $defaultGitSha,
    ) {
    }

    public function getBuildInfo(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $commitCount = $this->getGitValue('rev-list --count HEAD');
        $gitSha = $this->getGitValue('rev-parse --short HEAD');
        $remoteUrl = $this->getGitValue('remote get-url origin');

        $version = $this->baseVersion;
        if ($commitCount !== null && $commitCount !== '') {
            $version = sprintf('%s+%s', $this->baseVersion, $commitCount);
        }
        $displayVersion = $this->buildDisplayVersion($this->baseVersion, $commitCount);

        $commitUrl = null;
        if ($remoteUrl !== null && $gitSha !== null) {
            $repositoryUrl = $this->normalizeRepositoryUrl($remoteUrl);
            if ($repositoryUrl !== null) {
                $commitUrl = sprintf('%s/commit/%s', $repositoryUrl, $gitSha);
            }
        }

        $this->cached = [
            'version' => $version,
            'display_version' => $displayVersion,
            'git_sha' => $gitSha ?: $this->defaultGitSha,
            'commit_url' => $commitUrl,
        ];

        return $this->cached;
    }

    private function getGitValue(string $gitArgs): ?string
    {
        if (!is_dir($this->projectDir . '/.git')) {
            return null;
        }

        $command = sprintf(
            'git -C %s %s 2>/dev/null',
            escapeshellarg($this->projectDir),
            $gitArgs
        );

        $output = shell_exec($command);
        if ($output === null) {
            return null;
        }

        $value = trim($output);

        return $value !== '' ? $value : null;
    }

    private function normalizeRepositoryUrl(string $remoteUrl): ?string
    {
        $url = trim($remoteUrl);

        if (str_starts_with($url, 'git@github.com:')) {
            $url = 'https://github.com/' . substr($url, strlen('git@github.com:'));
        }

        if (!str_starts_with($url, 'https://github.com/')) {
            return null;
        }

        if (str_ends_with($url, '.git')) {
            $url = substr($url, 0, -4);
        }

        return rtrim($url, '/');
    }

    private function buildDisplayVersion(string $baseVersion, ?string $commitCount): string
    {
        if ($commitCount === null || $commitCount === '') {
            return $baseVersion;
        }

        $parts = explode('.', $baseVersion);
        if (count($parts) < 2) {
            return sprintf('%s.%s', $baseVersion, $commitCount);
        }

        return sprintf('%s.%s.%s', $parts[0], $parts[1], $commitCount);
    }
}
