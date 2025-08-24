<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$configurator = new Nette\Bootstrap\Configurator;
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config/common.neon');
$configurator->addConfig(__DIR__ . '/config/services.neon');
$container = $configurator->createContainer();

/** @var Symfony\Component\Console\Application $application */
$application = $container->getByType(Symfony\Component\Console\Application::class);
$application->run();
