<?php

namespace Infira\Klahvik\helper;

class DotConfig
{
	public static function load(string $path): array
	{
		if (!is_readable($path))
		{
			throw new \RuntimeException(sprintf('%s file is not readable', $path));
		}
		
		$config = [];
		$lines  = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line)
		{
			if (strpos(trim($line), '#') === 0)
			{
				continue;
			}
			
			[$name, $value] = explode('=', $line, 2);
			$name  = trim($name);
			$value = trim($value);
			
			if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV))
			{
				putenv(sprintf('%s=%s', $name, $value));
				$config[$name] = $value;
			}
		}
		
		return $config;
	}
}