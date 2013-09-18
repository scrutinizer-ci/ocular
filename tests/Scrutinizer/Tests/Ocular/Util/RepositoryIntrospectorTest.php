<?php

namespace Scrutinizer\Tests\Ocular\Util;

use Scrutinizer\Ocular\Util\RepositoryIntrospector;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RepositoryInspectorTest extends \PHPUnit_Framework_TestCase
{
    private $tmpDirs = array();

    public function testGetQualifiedName()
    {
        $tmpDir = $this->getTempDir();
        $this->installRepository('https://github.com/schmittjoh/metadata.git', $tmpDir);

        $introspector = new RepositoryIntrospector($tmpDir);
        $this->assertEquals('g/schmittjoh/metadata', $introspector->getQualifiedName());
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        foreach ($this->tmpDirs as $dir) {
            $fs->remove($dir);
        }
    }

    private function getTempDir()
    {
        $tmpDir = tempnam(sys_get_temp_dir(), 'ocular-intro');
        unlink($tmpDir);

        return $this->tmpDirs[] = $tmpDir;
    }

    private function installRepository($url, $dir)
    {
        $proc = new Process('git clone '.$url.' '.$dir);
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }
    }
}