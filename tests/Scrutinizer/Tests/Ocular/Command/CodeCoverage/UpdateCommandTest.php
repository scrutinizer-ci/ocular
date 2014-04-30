<?php

namespace Scrutinizer\Tests\Ocular\Command\CodeCoverage;

use Scrutinizer\Ocular\Command\CodeCoverage\UploadCommand;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Scrutinizer\Ocular\Util\RepositoryIntrospector;

class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDirs = array();
    protected $currentTmpDir;

    /**
     *
     * @var UploadCommand
     */
    protected $SUT;

    /**
     *
     * @var OutputInterface
     */
    protected $output;
    /**
     *
     * @var InputInterface
     */
    protected $input;

    public function setUp()
    {
        $this->SUT = new UploadCommand();

        $this->input = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');
    }

    public function testExecute()
    {
        $this->markTestIncomplete();
    }

    public function testGeneratePostData()
    {
        $this->markTestIncomplete();
    }

    public function testGetCoverageData()
    {
        $this->getTempDir(true, 0777);
        $this->installRepository();
        $subdir[] = md5(rand(0, 1000));
        $subdir[] = md5(rand(0, 1000));

        $subdir =  $this->currentTmpDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $subdir);
        mkdir($subdir, 0777, true);
        chdir($subdir);


        $buildDir =  $this->currentTmpDir . DIRECTORY_SEPARATOR . 'build';
        mkdir($buildDir, 0777, true);

        $coverageFile = $buildDir . DIRECTORY_SEPARATOR . 'coverage.xml';
        file_put_contents($coverageFile,
                          sprintf('<xml><file name="%1$s/test1"></file><file name="%1$s/test2"></file>' .
                                  '<file name="%1$s/test3"></file><file name="%1$s/test4"></file></xml>',
                                  $this->currentTmpDir));

        $reflection = $this->helperReflectionMethode($this->SUT, 'getCoverageData');
        $result = $reflection->invoke($this->SUT, $coverageFile);

        $this->assertInternalType('string', $result);
        $this->assertGreaterThan(2, strlen($result));


        $this->assertRegExp('#{scrutinizer_project_base_path}/test1#', $result);
        $this->assertRegExp('#{scrutinizer_project_base_path}/test2#', $result);
        $this->assertRegExp('#{scrutinizer_project_base_path}/test3#', $result);
        $this->assertRegExp('#{scrutinizer_project_base_path}/test4#', $result);
    }

    public function testGetBasePath()
    {
        $this->getTempDir(true, 0777);
        $this->installRepository();
        $subdir[] = md5(rand(0, 1000));
        $subdir[] = md5(rand(0, 1000));

        $subdir =  $this->currentTmpDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $subdir);

        mkdir($subdir, 0777, true);
        chdir($subdir);

        $reflection = $this->helperReflectionMethode($this->SUT, 'getBasePath');

        $result = $reflection->invoke($this->SUT);

        $this->assertInternalType('string', $result);
        $this->assertGreaterThan(2, strlen($result));
        $this->assertTrue(is_dir($subdir));
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetBasePathFail()
    {
        $this->getTempDir(true, 0777);
        chdir($this->currentTmpDir);

        $reflection = $this->helperReflectionMethode($this->SUT, 'getBasePath');
        $result = $reflection->invoke($this->SUT);
    }

//     /**
//      * @expectedException \RuntimeException
//      * @expectedExceptionMessage Please pass the format of the code coverage via the "--format" option, i.e. "--format=php-clover".
//      */
//     public function testParseFormatFail()
//     {
//         $reflection = $this->helperReflectionMethode($this->SUT, 'parseFormat');
//         $reflection->invoke($this->SUT, null);
//     }

    /**
     *
     * @dataProvider providerTestParseMethodWithNotEmpty
     */
    public function testParseMethodWithNotEmpty($method, $input, $expectedValue = null)
    {
        if ($expectedValue instanceof \Exception) {
            $this->setExpectedException(get_class($expectedValue), $expectedValue->getMessage());
        } elseif(empty($input)) {
            $expectedValue = $this->helperMockRepositoryIntrospector($method);
        } else {
            $expectedValue = $input;
        }


        $reflection = $this->helperReflectionMethode($this->SUT, $method);
        $result = $reflection->invoke($this->SUT, $input);

        $this->assertEquals($expectedValue, $result);
    }

    public function testConfigure()
    {
        $this->assertEquals('code-coverage:upload', $this->SUT->getName());
        $this->assertGreaterThan(2, strlen($this->SUT->getDescription()));

        $definition = $this->SUT->getDefinition();

        $this->assertTrue($definition->hasArgument('coverage-file'));
        $argument = $definition->getArgument('coverage-file');
        $this->assertInstanceOf('\Symfony\Component\Console\Input\InputArgument', $argument);
        $this->assertEquals('coverage-file', $argument->getName());
        $this->assertTrue($argument->isRequired());

        $optionsList = array(
            // name, isRequired, isArray
            array('api-url', true, false),
            array('repository', true, false),
            array('revision', true, false),
            array('format', true, false),
            array('parent', true, true)
        );

        foreach ($optionsList as $option) {
            $this->assertTrue($definition->hasOption($option[0]));
            $optionObj = $definition->getOption($option[0]);
            $this->assertInstanceOf('\Symfony\Component\Console\Input\InputOption', $optionObj);
            $this->assertEquals($option[0], $optionObj->getName());
            $this->assertSame($option[1], $optionObj->isValueRequired());
            $this->assertSame(!$option[1], $optionObj->isValueOptional());

            $this->assertSame($option[2], $optionObj->isArray());
        }
    }

    protected function helperReflectionMethode($object, $methodeName)
    {
        $reflection = new \ReflectionMethod($object, $methodeName);
        $reflection->setAccessible(true);

        return $reflection;
    }

    public function providerTestParseMethodWithNotEmpty()
    {
        return array(
            array('parseParents', array('test')),
            array('parseParents', array()),

            array('parseRevision', 'test'),
            array('parseRevision', null),

            array('parseRepositoryName', 'test'),
            array('parseRepositoryName', null),

            array('parseFormat', 'test'),
            array('parseFormat', null, new \RuntimeException('Please pass the format of the code coverage via the "--format" option, i.e. "--format=php-clover"')),
        );
    }

    private function exec($cmd, $dir = null)
    {
        $dir = $dir ?: $this->currentTmpDir;

        $proc = new Process($cmd, $dir ?: $this->currentTmpDir);
        if ($proc->run() !== 0) {
            throw new ProcessFailedException($proc);
        }

        return trim($proc->getOutput());
    }

    private function cloneRepository($url, $dir = null)
    {
        $this->installRepository($dir);
        $this->exec('git remote add origin ' . $url);
    }

    private function installRepository($dir = null)
    {
        $this->exec('git init', $dir);
        $this->exec('git config user.email "scrutinizer-ci@github.com"', $dir);
        $this->exec('git config user.name "Scrutinizer-CI"', $dir);
    }

    private function getTempDir($setDefault = true, $mkdir = false)
    {
        $tmpDir = tempnam(sys_get_temp_dir(), 'ocular-intro');
        unlink($tmpDir);

        if ($setDefault) {
            $this->currentTmpDir = $tmpDir;
        }
        if ($mkdir) {
            mkdir($tmpDir, $mkdir, true);
        }

        return $this->tmpDirs[] = $tmpDir;
    }

    /**
     *
     * @param string $methode
     * @return mixed expectedValue
     */
    protected function helperMockRepositoryIntrospector($methode)
    {
        $this->getTempDir(true, 0777);
        if ($methode === 'parseRepositoryName') {
            $this->cloneRepository('https://github.com/schmittjoh/metadata.git');
        } else {
            $this->installRepository();
        }
        chdir($this->currentTmpDir);

        $repositoryIntrospector = new RepositoryIntrospector($this->currentTmpDir);

        file_put_contents($this->currentTmpDir.'/foo', 'foo');
        $this->exec('git add . && git commit -m "adds foo"', $this->currentTmpDir);

        switch ($methode) {
            case "parseParents":
                return $repositoryIntrospector->getCurrentParents();
                break;
            case "parseRevision":
                return $repositoryIntrospector->getCurrentRevision();
                break;
            case "parseRepositoryName":
                return $repositoryIntrospector->getQualifiedName();
                break;
        }
    }
}