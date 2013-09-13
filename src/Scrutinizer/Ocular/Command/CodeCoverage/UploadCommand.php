<?php

namespace Scrutinizer\Ocular\Command\CodeCoverage;

use Guzzle\Service\Client;
use Scrutinizer\Ocular\Util\RepositoryIntrospector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class UploadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('code-coverage:upload')
            ->setDescription('Uploads code coverage information for an inspection to Scrutinizer.')
            ->addArgument('coverage-file', InputArgument::REQUIRED, 'The path to the code coverage file.')
            ->addOption('repository', null, InputOption::VALUE_REQUIRED, 'The qualified repository name of your repository (GitHub: g/login/username; Bitbucket: b/login/username).')
            ->addOption('revision', null, InputOption::VALUE_REQUIRED, 'The revision that the code coverage information belongs to (defaults to git rev-parse HEAD).')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The format of the code coverage file. Currently supported: php-clover')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $coverageFile = $input->getArgument('coverage-file');
        if ( ! is_file($coverageFile)) {
            throw new \InvalidArgumentException(sprintf('The coverage file "%s" does not exist.', $coverageFile));
        }

        $revision = $this->parseRevision($input->getOption('revision'));
        $repositoryName = $this->parseRepositoryName($input->getOption('repository'));

        $client = new Client('https://scrutinizer-ci.com/api{?access_token}', array(
            'access_token' => $input->getOption('access_token'),
        ));

        $output->write(sprintf('Uploading code coverage for repository "%s" and revision "%s"... ', $repositoryName, $revision));
        $client->post(
            'repositories/'.$repositoryName.'/data/code-coverage',
            array('Content-Type' => 'application/json'),
            json_encode(array(
                'revision' => $revision,
                'coverage' => array(
                    'format' => $input->getOption('format'),
                    'data' => base64_encode(file_get_contents($coverageFile)),
                ),
            ))
        )->send();
        $output->writeln('Done');
    }

    private function parseRepositoryName($name)
    {
        if ( ! empty($name)) {
            return $name;
        }

        return (new RepositoryIntrospector(getcwd()))->getQualifiedName();
    }

    private function parseRevision($inputRevision)
    {
        if ( ! empty($inputRevision)) {
            return $inputRevision;
        }

        return (new RepositoryIntrospector(getcwd()))->getCurrentRevision();
    }
}