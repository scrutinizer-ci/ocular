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
        $proc = $this->exec('git rev-parse HEAD');

        return trim($proc->getOutput());
    }

    public function getCurrentParents()
    {
        $proc = $this->exec('git log --pretty="%P" -n1 HEAD');

        return explode(' ', trim($proc->getOutput()));
    }

    /**
     *
     * @throws \RuntimeException
     * @return string
     */
    public function getQualifiedName()
    {
        $proc = $this->exec('git remote -v');

        $output = $proc->getOutput();

        $patterns = array(
            '#^origin\s+(?:git@|(?:git|https?)://)([^:/]+)(?:/|:)([^/]+)/([^/\s]+?)(?:\.git)?(?:\s|\n)#m',
            '#^[^\s]+\s+(?:git@|(?:git|https?)://)([^:/]+)(?:/|:)([^/]+)/([^/\s]+?)(?:\.git)?(?:\s|\n)#m',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $match)) {
                list(, $host, $login, $name) = $match;

                return $this->getRepositoryType($host).'/'.$login.'/'.$name;
            }
        }

        throw new \RuntimeException(sprintf("Could not extract repository name from:\n%s", $output));
    }

    /**
     *
     * @param string $host
     * @throws \LogicException
     * @return string
     */
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

    /**
     *
     * @param string $command
     * @param string $dir
     * @throws ProcessFailedException
     */
    protected function exec($command, $dir = null)
    {
        $proc = new Process($command, $dir ?: $this->dir);
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }
    }
}
