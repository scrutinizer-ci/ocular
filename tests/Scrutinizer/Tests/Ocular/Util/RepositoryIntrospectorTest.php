<?php

namespace Scrutinizer\Tests\Ocular\Util;

use Scrutinizer\Ocular\Util\RepositoryIntrospector;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RepositoryInspectorTest extends \PHPUnit_Framework_TestCase
{
    private $tmpDirs = array();

    public function repoUrlProvider()
    {
        return [
            ['git@github.com:schmittjoh/metadata.git'],
            ['https://github.com/schmittjoh/metadata.git'],
            ['https://ashon-ikon:2ae3578bfd30cbc9bb58861cf9f0fa742259cdb8@github.com/schmittjoh/metadata.git'],
        ];
    }

    /**
     * @dataProvider repoUrlProvider
     */
    public function testGetQualifiedName($url)
    {
        $tmpDir = $this->getTempDir();
        $this->installRepository($url, $tmpDir);

        $introspector = new RepositoryIntrospector($tmpDir);
        $this->assertEquals('g/schmittjoh/metadata', $introspector->getQualifiedName());
    }

    public function testGetCurrentParents()
    {
        $tmpDir = $this->getTempDir();
        mkdir($tmpDir, 0777, true);

        $this->exec('git init', $tmpDir);
        file_put_contents($tmpDir.'/foo', 'foo');
        $this->exec('git add . && git commit -m "adds foo"', $tmpDir);

        $introspector = new RepositoryIntrospector($tmpDir);
        $headRev = $introspector->getCurrentRevision();

        file_put_contents($tmpDir.'/bar', 'bar');
        $this->exec('git add . && git commit -m "adds bar"', $tmpDir);
        $this->assertEquals(array($headRev), $introspector->getCurrentParents());
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        foreach ($this->tmpDirs as $dir) {
            $fs->remove($dir);
        }
    }

    private function exec($cmd, $dir)
    {
        $proc = new Process($cmd, $dir);
        if ($proc->run() !== 0) {
            throw new ProcessFailedException($proc);
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
