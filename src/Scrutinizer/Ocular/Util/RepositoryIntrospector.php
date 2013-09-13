<?php

namespace Scrutinizer\Ocular\Util;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RepositoryIntrospector
{
    const TYPE_GITHUB = 'g';
    const TYPE_BITBUCKET = 'b';

    private $dir;

    public function __construct($repositoryDir)
    {
        $this->dir = $repositoryDir;
    }

    public function getCurrentRevision()
    {
        $proc = new Process('git rev-parse HEAD');
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }

        return trim($proc->getOutput());
    }

    public function getQualifiedName()
    {
        $proc = new Process('git remote -v');
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }

        $output = $proc->getOutput();

        $patterns = array(
            '#^origin\s+(?:git@|(?:git|https?)://)([^:/]+)(?:/|:)([^/]+)/([^/]+)\.git#',
            '#^[^\s]+\s+(?:git@|(?:git|https?)://)([^:/]+)(?:/|:)([^/]+)/([^/]+)\.git#',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $match)) {
                list(, $host, $login, $name) = $match;

                return $this->getRepositoryType($host).'/'.$login.'/'.$name;
            }
        }

        throw new \RuntimeException(sprintf("Could not extract repository name from:\n%s", $output));
    }

    private function getRepositoryType($host)
    {
        switch ($host) {
            case 'github.com':
                return self::TYPE_GITHUB;

            case 'bitbucket.org':
                return self::TYPE_BITBUCKET;

            default:
                throw new \LogicException(sprintf('Unknown host "%s".', $host));
        }
    }
}