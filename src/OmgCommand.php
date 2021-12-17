<?php

namespace Infira\omg;


use Symfony\Component\Console\Input\InputArgument;

class OmgCommand extends \Infira\console\Command
{
	public function __construct()
	{
		parent::__construct('create');
	}
	
	public function configure(): void
	{
		$this->addArgument('config', InputArgument::REQUIRED);
	}
	
	/**
	 * @throws \Exception
	 */
	public function runCommand()
	{
		$gen = new MainGenerator();
		$gen->loadConfig($this->input->getArgument('config'));
		$gen->loadAPI();
		$gen->make();
	}
}