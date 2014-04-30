<?php

namespace Scrutinizer\Tests\Ocular\Command\CodeCoverage;

use Scrutinizer\Ocular\Command\CodeCoverage\UploadCommand;

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
        $this->markTestIncomplete();
    }

    public function testGetBasePath()
    {
        $this->getTempDir();
        chdir($this->currentTmpDir);

        $reflection = $this->helperReflectionMethode($this->SUT, 'getBasePath');

        $result = $reflection->invoke($this->SUT);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Please pass the format of the code coverage via the "--format" option, i.e. "--format=php-clover".
     */
    public function testParseFormatFail()
    {
        $reflection = $this->helperReflectionMethode($this->SUT, 'parseFormat');
        $reflection->invoke($this->SUT, null);
    }

    public function testParseRepositoryName()
    {
        $this->markTestIncomplete();
    }
    public function testParseParents()
    {
        $this->markTestIncomplete();
    }

    public function testParseRevision()
    {
        $this->markTestIncomplete();
    }

    /**
     *
     * @dataProvider providerTestParseMethodWithNotEmpty
     */
    public function testParseMethodWithNotEmpty($method, $input)
    {
        $reflection = $this->helperReflectionMethode($this->SUT, $method);
        $result = $reflection->invoke($this->SUT, $input);

        $this->assertEquals($input, $result);
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
            array('parseRevision', 'test'),
            array('parseRepositoryName', 'test'),
            array('parseFormat', 'test'),
        );
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
}