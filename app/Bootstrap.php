<?php

declare(strict_types=1);

namespace App;

use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	private Configurator $configurator;
	private string $rootDir;


	public function __construct()
	{
		$this->rootDir = dirname(__DIR__);
		$this->configurator = new Configurator;
		$this->configurator->setTempDirectory($this->rootDir . '/temp');
	}


	public function bootWebApplication(): Nette\DI\Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function bootConsoleApplication(): Nette\DI\Container
	{
		$this->configurator->setDebugMode(true);
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	/** Kontejner pro integrační testy – DB noctis_test (config/test.neon), vývojový režim (Doctrine generuje proxy) */
	public function bootTestContainer(): Nette\DI\Container
	{
		$this->configurator->setDebugMode(true);
		$this->setupContainer();
		$this->configurator->addConfig($this->rootDir . '/config/test.neon');
		return $this->configurator->createContainer();
	}


	public function initializeEnvironment(): void
	{
		//$this->configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$this->configurator->enableTracy($this->rootDir . '/log');

		$loader = $this->configurator->createRobotLoader();
    	$loader->addDirectory($this->rootDir);
    	$loader->addDirectory($this->rootDir . '/src');
		$loader->ignoreDirs = array_merge($loader->ignoreDirs ?? [], [
    		'vendor', 'tests', 'Tests'
		]);
    	$loader->register();
	}


	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');
	}
}
