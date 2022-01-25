<?php

namespace Infira\omg;

class Config
{
	private static $_isLoaded = false;
	
	public static $mandatoryResponseProperties = false;
	public static $spec                        = '';
	public static $destination                 = '';
	public static $rootNamespace               = '';
	public static $pathNamespaceTemplate       = '{path[0]}/{method}/{operationID?generateName(path[1:*])}';
	public static $operationInputParameterName = 'rb';
	public static $laravel                     = false;
	public static $nullableDefaultNull         = true;//when object or property has nullable and default is not defined then handle default as null
	public static $phpVersion                  = 7.3;
	public static $httpStatusNameMap           = [];
	public static $operationClass              = null;
	public static $operationTraits             = [];
	public static $operationImplements         = [];
	
	public static function load(array $config)
	{
		foreach ($config as $name => $value) {
			if (!property_exists(self::class, $name)) {
				Omg::error("Config '$name' does not exist");
			}
			$type = gettype(self::$$name);
			if ($type == 'boolean') {
				$value = (bool)$value;
			}
			self::$$name = $value;
		}
		self::$_isLoaded = true;
	}
	
	public static function isLoaded(): bool
	{
		return self::$_isLoaded;
	}
	
	public static function getRootNamespace(): string
	{
		return str_replace(['/', '\\\\'], '\\', self::$rootNamespace);
	}
	
	public static function getHttpStatusName(string $statusCode): string
	{
		return self::$httpStatusNameMap[$statusCode] ?? $statusCode;
	}
	
	public static function getOperationClass(): ?string
	{
		return self::$operationClass;
	}
	
	public static function getOperationTraits(): array
	{
		if (!is_array(self::$operationTraits)) {
			return [];
		}
		
		return self::$operationTraits;
	}
	
	public static function getOperationInterfaces(): array
	{
		if (!is_array(self::$operationImplements)) {
			return [];
		}
		
		return self::$operationImplements;
	}
}