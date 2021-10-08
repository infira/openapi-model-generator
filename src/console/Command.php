<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Infira\Klahvik\helper\Server;
use Infira\Klahvik\helper\Local;
use Symfony\Component\Console\Helper\ProgressBar;
use Infira\Klahvik\helper\SymfonyStyle;

class Command extends \Symfony\Component\Console\Command\Command
{
	protected OutputInterface $output;
	protected InputInterface  $input;
	protected Server          $remote;
	protected Server          $vagrant;
	protected Local           $local;
	public ProgressBar        $progress;
	
	protected ?string $namespace = null;
	protected ?string $name      = null;
	
	private array $opt = [];
	
	public function __construct()
	{
		if ($this->name === null)
		{
			throw new \Exception('command name not defined');
		}
		if ($this->namespace and $this->name and $this->namespace != $this->name)
		{
			parent::__construct("$this->namespace:$this->name");
		}
		else
		{
			parent::__construct("$this->name");
		}
	}
	
	public function configure(): void
	{
		$this->configServers();
	}
	
	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		set_time_limit(7200);
		$this->output = &$output;
		$this->input  = &$input;
		$this->beforeExecute();
		$this->runCommand();
		$this->afterExecute();
		
		return $this->success();
	}
	
	protected function setRemoteServer(string $user, string $host, string $klahvikPatH)
	{
		$this->remote = new Server($this, $user, $host);
		$this->remote->setKlahvikPath($klahvikPatH);
	}
	
	protected function setVagrantServeer(string $user, string $host, string $klahvikPatH)
	{
		$this->vagrant = new Server($this, $user, $host);
		$this->vagrant->setKlahvikPath($klahvikPatH);
	}
	
	protected function setLocal(string $klahvikPatH)
	{
		$this->local = new Local();
		$this->local->setKlahvikPath($klahvikPatH);
	}
	
	protected function opt(string $name, $value = null)
	{
		if ($value === null)
		{
			if (array_key_exists($name, $this->opt))
			{
				return $this->opt[$name];
			}
			
			return null;
		}
		$this->opt[$name] = $value;
		
		return $this->opt[$name];
	}
	
	//region output messages
	public function error(string $msg)
	{
		$this->say("<error>$msg</error>");
		exit;
	}
	
	public function info(string $msg)
	{
		$this->say("<info>$msg</info>");
	}
	
	public function blink($msg)
	{
		$outputStyle = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']);
		$this->output->getFormatter()->setStyle('fire', $outputStyle);
		$this->output->writeln("<fire>$msg</>");
	}
	
	public function say(string $message, string $suffix = '')
	{
		array_map(function ($line) use ($suffix)
		{
			$line = trim($line);
			if ($line)
			{
				$says = $suffix ? "$suffix says" : 'says';
				$this->output->writeln("<comment>klahvik $says: </comment>$line");
			}
		}, explode("\n", $message));
	}
	
	//endregion
	
	protected function progress(callable $command)
	{
		// creates a new progress bar (50 units)
		$this->progress = new ProgressBar($this->output, 50);
		
		// starts and displays the progress bar
		$this->progress->start();
		$command();
		$this->progress->finish();
	}
	
	protected function section(string $message, callable $command)
	{
		$io = new SymfonyStyle($this->input, $this->output);
		$io->blockSection($message, $command);
	}
	
	protected function success(): int
	{
		return \Symfony\Component\Console\Command\Command::SUCCESS;
	}
	
	protected function beforeExecute() { }
	
	protected function afterExecute()
	{
		//void
	}
	
	protected function configServers() { }
}