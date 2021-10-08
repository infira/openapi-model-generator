<?php

namespace Infira\Klahvik\helper;

use Infira\Utils\Dir;

class Local
{
	private ?string $klahvikPath;
	
	public function setKlahvikPath(string $klahvikPath): void
	{
		$this->klahvikPath = Dir::fixPath($klahvikPath);
	}
	
	public function klahvikPath(string $path = ''): string
	{
		return $this->klahvikPath . $path;
	}
}