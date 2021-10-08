<?php

namespace Infira\Klahvik\helper;

abstract class OptManager
{
	public static array $entries = [];
	
	public static array $defaultValues = [];
	
	public final static function addDefaultValue(string $name, $value)
	{
		self::$defaultValues[$name] = $value;
	}
	
	public final static function setGetVar(string $name, $value = null, string $setDefinition = null)
	{
		if ($value !== null)
		{
			self::$entries[$name] = $value;
			if ($setDefinition)
			{
				define($setDefinition, $value);
			}
		}
		
		if (array_key_exists($name, self::$entries))
		{
			return self::$entries[$name];
		}
		elseif (array_key_exists($name, self::$defaultValues))
		{
			return self::$defaultValues[$name];
		}
		
		return null;
	}
}