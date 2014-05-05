<?php

namespace Scrutinizer\Tests\Ocular;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

abstract class AbstractTestCaseClass extends \PHPUnit_Framework_TestCase
{
    protected $tmpDirs = array();
    protected $currentTmpDir;

    protected function exec($cmd, $dir = null)
    {
        $dir = $dir ?: $this->currentTmpDir;

        $proc = new Process($cmd, $dir ?: $this->currentTmpDir);
        if ($proc->run() !== 0) {
            throw new ProcessFailedException($proc);
        }

        return trim($proc->getOutput());
    }

    protected function getTempDir($setDefault = true, $mkdir = false)
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

    protected function cloneRepository($url, $dir = null)
    {
        $this->installRepository($dir);
        $this->exec('git remote add origin ' . $url);
    }

    protected function installRepository($dir = null)
    {
        $this->exec('git init', $dir);
        $this->exec('git config user.email "scrutinizer-ci@github.com"', $dir);
        $this->exec('git config user.name "Scrutinizer-CI"', $dir);
    }

    protected function helperReflectionMethode($object, $methodeName)
    {
        $reflection = new \ReflectionMethod($object, $methodeName);
        $reflection->setAccessible(true);

        return $reflection;
    }

    /**
     *
     * @param string|object $object
     * @param string $propertyName
     * @return \ReflectionProperty
     */
    protected function helperReflectionProperty($object, $propertyName)
    {
        $objectReflection = new \ReflectionObject($object);
        $reflection = $objectReflection;

        while ($reflection = $reflection->getParentClass()) {
            if ($reflection->hasProperty($propertyName)) {
                break;
            }
        }

        if (!$reflection->hasProperty($propertyName)) {
            throw new \InvalidArgumentException(sprintf(
                'property (%s) is not exists in the object from class "%s"',
                $propertyName,
                get_class($object)
            ));
        }

        $reflectionProperty = $reflection->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     *
     * @param string|object $object
     * @param string $propertyName
     * @return \ReflectionProperty
     */
    protected function skipIfPhpVersionDonotSupportBuildInServer()
    {
        if (version_compare(phpversion(), "5.4", '>=')) {
            return;
        }
        $this->markTestSkipped("php lower then 5.4 don't support buildin Server");
    }
}
