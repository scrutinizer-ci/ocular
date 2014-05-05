<?php

$loader = require __DIR__.'/../vendor/autoload.php';

$loader->add('Scrutinizer\Tests\Ocular', __DIR__);


\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
