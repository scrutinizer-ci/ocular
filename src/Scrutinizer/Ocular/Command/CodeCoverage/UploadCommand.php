<?php

namespace Scrutinizer\Ocular\Command\CodeCoverage;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Plugin\Backoff\BackoffPlugin;
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
            ->addOption('api-url', null, InputOption::VALUE_REQUIRED, 'The base URL of the API.', 'https://scrutinizer-ci.com/api')
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
        $format = $this->parseFormat($input->getOption('format'));

        $client = new Client($input->getOption('api-url').'{?access_token}', array(
            'access_token' => $input->getOption('access-token'),
            'request.options' => array(
                'Content-Type' => 'application/json',
            )
        ));
        $client->addSubscriber(BackoffPlugin::getExponentialBackoff());

        $output->write(sprintf('Uploading code coverage for repository "%s" and revision "%s"... ', $repositoryName, $revision));
        try {
            $client->post(
                'repositories/'.$repositoryName.'/data/code-coverage{?access_token}',
                null,
                json_encode(array(
                    'revision' => $revision,
                    'coverage' => array(
                        'format' => $format,
                        'data' => base64_encode($this->getCoverageData($coverageFile)),
                    ),
                ))
            )->send();
            $output->writeln('Done');

            return 0;
        } catch (BadResponseException $ex) {
            $output->writeln("<error>Failed</error>");

            if ($ex instanceof ClientErrorResponseException) {
                $output->writeln('<error>'.$ex->getResponse()->getBody(true).'</error>');

                return 1;
            }

            throw $ex;
        }
    }

    private function getCoverageData($file)
    {
        $content = file_get_contents($file);
        $content = str_replace($this->getBasePath(), '{scrutinizer_project_base_path}', $content);

        return $content;
    }

    private function getBasePath()
    {
        $dir = getcwd();
        while ( ! empty($dir)) {
            if (is_dir($dir.'/.git')) {
                return $dir;
            }

            $dir = dirname($dir);
        }

        throw new \LogicException('Could not determine base path for project.');
    }

    private function parseFormat($format)
    {
        if (empty($format)) {
            throw new \RuntimeException('Please pass the format of the code coverage via the "--format" option, i.e. "--format=php-clover".');
        }

        return $format;
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