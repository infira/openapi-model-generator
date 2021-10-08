<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Infira\Klahvik\helper\Server;
use Infira\Klahvik\helper\Local;
use Symfony\Component\Console\Helper\ProgressBar;
use Infira\Klahvik\helper\SymfonyStyle;
use Infira\Utils\Dir;
use Infira\Klahvik\helper\DotConfig;

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
	
	private function config()
	{
		$configFile = Dir::fixPath($_SERVER['HOME']) . '/.klahvik';
		$this->opt('LOCAL_TMP_PATH', KLAHVIK_PATH . 'tmp/');
		if (file_exists($configFile))
		{
			$conf = DotConfig::load($configFile);
			array_walk($conf, fn($value, $name) => $this->opt($name, $value));
		}
		$this->configureRemote();
		$requiredPaths = ['LOCAL_TMP_PATH', 'VAGRANT_KLAHVIK_PATH', 'VAGRANT_TMP_PATH', 'REMOTE_KLAHVIK_PATH', 'REMOTE_TMP_PATH'];
		foreach ($requiredPaths as $name)
		{
			if (!$this->opt($name))
			{
				$this->error("config $name is required");
			}
			$this->opt($name, Dir::fixPath($this->opt($name)));
		}
		$this->local = new Local($this);
		
		$this->vagrant = new Server($this, $this->opt('VAGRANT_USER'), $this->opt('VAGRANT_HOST'));
		$this->vagrant->setKlahvikPath($this->opt('VAGRANT_KLAHVIK_PATH'));
		$this->vagrant->setTmpPath($this->opt('VAGRANT_TMP_PATH'));
		
		
		if (!$this->opt('REMOTE_USER'))
		{
			$this->error('REMOTE_USER is not defined');
		}
		if (!$this->opt('REMOTE_HOST'))
		{
			$this->error('REMOTE_HOST is not defined');
		}
		$this->remote = new Server($this, $this->opt('REMOTE_USER'), $this->opt('REMOTE_HOST'));
		$this->remote->setKlahvikPath($this->opt('REMOTE_KLAHVIK_PATH'));
		$this->remote->setTmpPath($this->opt('REMOTE_TMP_PATH'));
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
		$this->config();
		$this->beforeExecute();
		$this->runCommand();
		$this->afterExecute();
		
		return $this->success();
	}
	
	public function opt(string $name, $value = null)
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
	
	protected function configureRemote() { }
}