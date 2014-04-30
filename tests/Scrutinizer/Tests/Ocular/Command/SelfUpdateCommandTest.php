<?php

namespace Scrutinizer\Tests\Ocular\Command;

use Scrutinizer\Ocular\Command\SelfUpdateCommand;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var SelfUpdateCommand
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
        $this->SUT = new SelfUpdateCommand();

        $this->input = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "self-update" command is only available for compiled phar files which you can obtain at "https://scrutinizer-ci.com/ocular.phar".
     *
     */
    public function testExecuteWithNonCompiledVersion()
    {
        $reflection = $this->helperReflectionMethode($this->SUT, 'execute');
        $reflection->invoke($this->SUT, $this->input, $this->output);
    }

    public function testExecuteWithOfflineRevisionValue()
    {
        $this->markTestIncomplete();
    }

    public function testExecuteUp2Date()
    {
        $this->markTestIncomplete();
    }

    public function testExecuteCanNotDownload()
    {
        $this->markTestIncomplete();
    }

    public function testExecuteCanNotDeployNewVersion()
    {
        $this->markTestIncomplete();
    }

    public function testExecuteWorkFine()
    {
        $this->markTestIncomplete();
    }

    public function testConfigure()
    {
        $this->assertEquals('self-update', $this->SUT->getName());
        $this->assertGreaterThan(2, strlen($this->SUT->getDescription()));
    }

    protected function helperReflectionMethode($object, $methodeName)
    {
        $reflection = new \ReflectionMethod($object, $methodeName);
        $reflection->setAccessible(true);

        return $reflection;
    }
}