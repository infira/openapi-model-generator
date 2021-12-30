<?php

namespace Infira\omg\helper;

use Nette\PhpGenerator\Printer;

class NettePrinter extends Printer
{
	public function __construct()
	{
		$this->wrapLength          = 400;
		$this->linesBetweenMethods = 1;
		parent::__construct();
	}
	
}