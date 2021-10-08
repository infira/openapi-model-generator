<?php

namespace Infira\Klahvik\console\gws;

use Infira\Klahvik\helper\OptManager;

class Opt extends OptManager
{
	public static function databases(array $config = null): ?array
	{
		return self::setGetVar("domains", $config);
	}
}