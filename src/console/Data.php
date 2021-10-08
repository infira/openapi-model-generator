<?php

namespace Infira\Klahvik\console;

use Symfony\Component\Console\Input\InputArgument;

class Data extends Command
{
	protected ?string $namespace = 'data';
	protected ?string $name      = 'data';
	
	public function configure(): void
	{
		$this->addArgument('sync', InputArgument::REQUIRED);
	}
	
	protected function setSyncDefaultPath(string $src, string $dest)
	{
		$this->opt('syncSrc', $src);
		$this->opt('syncDest', $dest);
	}
	
	public function runCommand()
	{
		if ($this->input->getArgument('sync'))
		{
			if (!$this->opt('syncSrc') or !$this->opt('syncDest'))
			{
				$this->error('sync source and destionation undefined');
			}
			$this->remote->rsync($this->opt('syncSrc'), $this->opt('syncDest'));
		}
	}
}