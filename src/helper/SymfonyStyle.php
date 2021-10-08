<?php

namespace Infira\Klahvik\helper;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;

class SymfonyStyle extends \Symfony\Component\Console\Style\SymfonyStyle
{
	public function blockSection(string $message, callable $command)
	{
		$eq = sprintf('<comment>%s</>', str_repeat('=', Helper::width(Helper::removeDecoration($this->getFormatter(), $message)) + 25));
		$this->writeln([
			sprintf('<comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)),
			$eq,
		]);
		$command();
		$this->writeln($eq);
		$this->newLine();
	}
}