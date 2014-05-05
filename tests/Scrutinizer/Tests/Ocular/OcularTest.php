<?php

namespace Scrutinizer\Tests\Ocular;

use Scrutinizer\Ocular\Ocular;

class OcularTest extends AbstractTestCaseClass
{
    /**
     *
     * @var Ocular
     */
    protected $SUT;


    public function setUp()
    {
        $this->SUT = new Ocular();
    }


    public function testGetDefaultInputDefinition()
    {
        $definition = $this->SUT->getDefinition();

        $optionsList = array(
            // name, isRequired, isArray
            array('access-token', true, false),
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

    public function testRegisterCommands()
    {
        // setup
        $registerCommands = $this->helperReflectionMethode($this->SUT, 'registerCommands');

        $commands = $this->helperReflectionProperty($this->SUT, 'commands');
        $commands->setValue($this->SUT, array());
        $this->assertCount(0, $commands->getValue($this->SUT));

        // run
        $registerCommands->invoke($this->SUT);

        // assert
        $this->assertNotCount(0, $commands->getValue($this->SUT));
        $this->assertCount(2, $commands->getValue($this->SUT));

        $assertCommands = array(
            'code-coverage:upload' => '\Scrutinizer\Ocular\Command\CodeCoverage\UploadCommand',
            'self-update'          => '\Scrutinizer\Ocular\Command\SelfUpdateCommand',
        );
        foreach ($assertCommands as $commandName => $commandClass) {
            $this->assertTrue($this->SUT->has($commandName));
            $this->assertInstanceOf($commandClass, $this->SUT->get($commandName));
        }
    }
    /**
     *
     * @dataProvider providerTrueFalse
     */
    public function testLoadConfiguration($withConfigFile)
    {
        if ($withConfigFile) {
            putenv('HOME=' . __DIR__ . '/_files');
        } else {
            putenv('HOME=' . __DIR__);
        }

        $loadConfiguration = $this->helperReflectionMethode($this->SUT, 'loadConfiguration');
        $result = $loadConfiguration->invoke($this->SUT);

        $this->assertInstanceOf('\Scrutinizer\Ocular\Configuration', $result);
        $this->assertInstanceOf($withConfigFile ? '\PhpOption\Some' : '\PhpOption\None', $result->getAccessToken());
    }

    public function providerTrueFalse ()
    {
        return array(
            array(true),
            array(false)
        );
    }
}
