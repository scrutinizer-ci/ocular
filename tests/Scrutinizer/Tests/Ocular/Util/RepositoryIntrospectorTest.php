<?php

namespace Scrutinizer\Tests\Ocular\Util;

use Scrutinizer\Ocular\Util\RepositoryIntrospector;
use Scrutinizer\Tests\Ocular\AbstractTestCaseClass;

use Symfony\Component\Filesystem\Filesystem;

class RepositoryInspectorTest extends AbstractTestCaseClass
{

    public function setUp()
    {
        $this->getTempDir(true, 0777);
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function testGetQualifiedNameNonGitRepo()
    {
        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $introspector->getQualifiedName();
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testGetQualifiedNameWithNoRemote()
    {
        $this->installRepository();

        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $introspector->getQualifiedName();
    }

    public function testGetQualifiedName()
    {
        $this->cloneRepository('https://github.com/schmittjoh/metadata.git');

        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $this->assertEquals('g/schmittjoh/metadata', $introspector->getQualifiedName());
    }

    public function testGetCurrentRevision()
    {
        $this->installRepository();

        file_put_contents($this->currentTmpDir.'/foo', 'foo');
        $this->exec('git add . && git commit -m "adds foo"', $this->currentTmpDir);

        $expectedRev = $this->exec('git rev-parse HEAD', $this->currentTmpDir);

        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $headRev = $introspector->getCurrentRevision();

        $this->assertInternalType('string', $headRev);
        $this->assertEquals($expectedRev, $headRev);
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function testGetCurrentRevisionFail()
    {
        $this->installRepository();

        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $headRev = $introspector->getCurrentRevision();
    }

    /**
     * @depends testGetCurrentRevision
     */
    public function testGetCurrentParents()
    {
        $this->installRepository();

        file_put_contents($this->currentTmpDir.'/foo', 'foo');
        $this->exec('git add . && git commit -m "adds foo"', $this->currentTmpDir);

        $introspector = new RepositoryIntrospector($this->currentTmpDir);
        $headRev = $introspector->getCurrentRevision();

        file_put_contents($this->currentTmpDir.'/bar', 'bar');
        $this->exec('git add . && git commit -m "adds bar"', $this->currentTmpDir);
        $this->assertEquals(array($headRev), $introspector->getCurrentParents());
    }

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function testGetCurrentParentsFail()
    {
        $this->installRepository();

        $introspector = new RepositoryIntrospector($this->currentTmpDir);

        $introspector->getCurrentParents();
    }

    /**
     *
     * @dataProvider providerTestGetRepositoryType
     */
    public function testGetRepositoryType($hosts, $exceptionName)
    {
        if (strlen($exceptionName) > 1) {
            $this->setExpectedException($exceptionName);
        }

        $tmpDir = $this->getTempDir();
        mkdir($tmpDir, 0777, true);
        $introspector = new RepositoryIntrospector($tmpDir);

        $reflection = new \ReflectionMethod($introspector, 'getRepositoryType');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($introspector, $hosts);

        $this->assertEquals($exceptionName, $result);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        foreach ($this->tmpDirs as $dir) {
            $fs->remove($dir);
        }
    }

    public function providerTestGetRepositoryType()
    {
        return array(
            array('github.com','g'),
            array('bitbucket.org','b'),
            array('gitlab.com','\LogicException')
        );
    }
}
