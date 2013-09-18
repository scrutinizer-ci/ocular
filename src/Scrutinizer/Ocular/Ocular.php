<?php

namespace Scrutinizer\Ocular;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

class Ocular extends Application
{
    const VERSION = '@revision@';

    private $cfg;

    public function __construct()
    {
        $this->cfg = $this->loadConfiguration();
        parent::__construct('ocular', self::VERSION);

        $this->registerCommands();
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('access-token', null, InputOption::VALUE_REQUIRED, 'The access token to use when communicating with scrutinizer-ci.com', $this->cfg->getAccessToken()->getOrElse(null)));

        return $definition;
    }

    private function registerCommands()
    {
        $this->add(new Command\CodeCoverage\UploadCommand());
        $this->add(new Command\SelfUpdateCommand());
    }

    private function loadConfiguration()
    {
        $homeDir = getenv('HOME');
        if ( ! is_file($homeDir.'/.ocular/config.json')) {
            return new Configuration();
        }

        return SerializerBuilder::create()->build()->deserialize(
            file_get_contents($homeDir.'/.ocular/config.json'),
            'Scrutinizer\Ocular\Configuration',
            'json'
        );
    }
}