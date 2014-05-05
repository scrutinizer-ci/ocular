<?php

namespace Scrutinizer\Tests\Ocular\Command\CodeCoverage;

use Scrutinizer\Ocular\Command\CodeCoverage\UploadCommand;
use Scrutinizer\Ocular\Util\RepositoryIntrospector;
use Scrutinizer\Tests\Ocular\AbstractTestCaseClass;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommandTest extends AbstractTestCaseClass
{
    /**
     *
     * @var UploadCommand
     */
    protected $SUT;

    /**
     *
     * @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $output;
    /**
     *
     * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $input;

    protected $inputGetOptionMap = array();

    public function setUp()
    {
        $this->SUT = new UploadCommand();

        $this->input = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');
    }

    /**
     * @dataProvider providerTestExecute
     */
    public function testExecute($withCoverageFile, $statusCode = '200')
    {
        $this->skipIfPhpVersionDonotSupportBuildInServer();

        $repositoryName = "scrutinizer-ocular";
        $accessToken = md5("success");
        $revision = md5('rev1');

        $this->inputGetOptionMap[] = array('repository', &$repositoryName);
        $this->inputGetOptionMap[] = array('api-url', "http://localhost:8080/");
        $this->inputGetOptionMap[] = array('access-token', &$accessToken);

        $this->helperMockGeneratePostData($withCoverageFile, $revision);

        $this->input->expects($this->any())
                    ->method('getOption')
                    ->will($this->returnValueMap($this->inputGetOptionMap));

        if ($statusCode === '200') {
            $this->output->expects($this->once())
            ->method('writeln')
            ->with('Done');
        } else {
            $this->output->expects($this->at(1))
                         ->method('writeln')
                         ->with('<error>Failed</error>');

            if ($statusCode === '403') {
                $accessToken = md5('no access');
                $this->output->expects($this->at(2))
                             ->method('writeln')
                             ->with('<error>no access with the token "' . var_export(array($accessToken, $accessToken), true) .'"</error>');
            } else {
                $repositoryName = 'with-internal-server-error';
            }
        }

        if (!$withCoverageFile) {
            $message = sprintf(
                'Notifying that no code coverage data is available for repository "%s" and revision "%s"... ',
                $repositoryName,
                $revision
            );
        } else {
            $message = sprintf(
                'Uploading code coverage for repository "%s" and revision "%s"... ',
                $repositoryName,
                $revision
            );
        }

        $this->output->expects($this->once())
                     ->method('write')
                     ->with($message);

        $reflection = $this->helperReflectionMethode($this->SUT, 'execute');

        if ($statusCode === '500') {
            $this->setExpectedException('\Guzzle\Http\Exception\BadResponseException');
        }

        $result = $reflection->invoke($this->SUT, $this->input, $this->output);

        if ($statusCode === '200') {
            $this->assertEquals(0, $result);
        } elseif (substr($statusCode, 0, 1) === '4') {
            $this->assertEquals(1, $result);
        }
    }

    public function testGeneratePostData()
    {
        $this->helperMockGeneratePostData(true);

        $this->input->expects($this->any())
                    ->method('getOption')
                    ->will($this->returnValueMap($this->inputGetOptionMap));


        $reflection = $this->helperReflectionMethode($this->SUT, 'generatePostData');
        $result = $reflection->invoke($this->SUT, $this->input);

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('revision', $result);
        $this->assertArrayHasKey('parents', $result);
        $this->assertArrayHasKey('coverage', $result);
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
        file_put_contents(
            $coverageFile,
            sprintf(
                '<xml><file name="%1$s/test1"></file><file name="%1$s/test2"></file>' .
                '<file name="%1$s/test3"></file><file name="%1$s/test4"></file></xml>',
                $this->currentTmpDir
            )
        );

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

    /**
     *
     * @dataProvider providerTestParseMethodWithNotEmpty
     */
    public function testParseMethodWithNotEmpty($method, $input, $expectedValue = null)
    {
        if ($expectedValue instanceof \Exception) {
            $this->setExpectedException(get_class($expectedValue), $expectedValue->getMessage());
        } elseif (empty($input)) {
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

    public function providerTestExecute()
    {
        return array(
            array(true, '200'),
            array(false, '200'),

            array(true, '403'),
            array(false, '403'),

            array(true, '500'),
            array(false, '500'),
        );
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
    protected function helperMockGeneratePostData($withCoverage, $revision = null)
    {
        $this->getTempDir(true, 0777);
        $this->installRepository();
        chdir($this->currentTmpDir);


        $buildDir =  $this->currentTmpDir . DIRECTORY_SEPARATOR . 'build';
        mkdir($buildDir, 0777, true);


        $coverageFile = $buildDir . DIRECTORY_SEPARATOR . 'coverage.xml';

        $this->inputGetOptionMap[] = array('revision', $revision);
        $this->inputGetOptionMap[] = array('parent', array());
        $this->inputGetOptionMap[] = array('format', "--format=php-clover");

        $this->input->expects($this->any())
                    ->method('getArgument')
                    ->with('coverage-file')
                    ->will($this->returnValue($coverageFile));


        file_put_contents($this->currentTmpDir.'/foo', 'foo');
        $this->exec('git add . && git commit -m "adds foo"', $this->currentTmpDir);


        if ($withCoverage) {
            file_put_contents(
                $coverageFile,
                sprintf(
                    '<xml><file name="%1$s/test1"></file><file name="%1$s/test2"></file>' .
                    '<file name="%1$s/test3"></file><file name="%1$s/test4"></file></xml>',
                    $this->currentTmpDir
                )
            );
        }
    }
}
