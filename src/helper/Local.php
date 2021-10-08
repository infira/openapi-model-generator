<?php

namespace Infira\Klahvik\helper;

use Infira\Klahvik\console\Command;

class Local
{
	private Command $cmd;
	
	public function __construct(Command &$cmd)
	{
		$this->cmd = &$cmd;
	}
	
	public function tmp(string $path = ''): string
	{
		return $this->cmd->opt('LOCAL_TMP_PATH') . $path;
	}
}