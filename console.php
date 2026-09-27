<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$container = (new App\Bootstrap)->bootConsoleApplication();

/** @var Symfony\Component\Console\Application $application */
$application = $container->getByType(Symfony\Component\Console\Application::class);
exit($application->run());
