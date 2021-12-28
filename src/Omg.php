<?php

namespace Infira\omg;

use cebe\openapi\spec\Schema;
use Infira\omg\generator\SchemaObjectModel;
use Infira\omg\generator\SchemaArrayModel;
use cebe\openapi\spec\Reference;
use Infira\omg\generator\SchemaBlankModel;

class Omg
{
	private static $generatedItems = [];
	
	public static function isGenerated(string $cid): bool
	{
		return isset(self::$generatedItems[$cid]);
	}
	
	public static function getGenerated(string $cid): string
	{
		return self::$generatedItems[$cid];
	}
	
	public static function setGenerated(string $classPath, string $schemaLocation)
	{
		self::$generatedItems[$classPath] = $schemaLocation;
	}
	
	/**
	 * @param string                $namespace
	 * @param string                $schemaLocation
	 * @param string|null           $type
	 * @param Reference|Schema|null $schema
	 * @return \Infira\omg\generator\SchemaArrayModel|\Infira\omg\generator\SchemaBlankModel|\Infira\omg\generator\SchemaObjectModel
	 */
	public static function getGenerator(?string $type, string $namespace, string $schemaLocation, $schema = null)
	{
		if ($type == 'object') {
			$generator = new SchemaObjectModel($namespace, $schemaLocation);
		}
		elseif ($type == 'array') {
			$generator = new SchemaArrayModel($namespace, $schemaLocation);
		}
		else {
			$generator = new SchemaBlankModel($namespace, $schemaLocation);
		}
		$generator->setSchema($schema);
		
		return $generator;
	}
	
	public static function validateSchema(Schema &$schema)
	{
		if (!$schema->properties and $schema->allOf) {
			$schema->properties = [];
			foreach ($schema->allOf as $object) {
				if ($object instanceof Reference) {
					$schema->properties = array_merge($schema->properties, $object->resolve()->properties);
				}
				else {
					$schema->properties = array_merge($schema->properties, $object->properties);
				}
			}
		}
		
		if ($schema->properties and !$schema->type) {
			$schema->type = 'object';
		}
		
		if ($schema->type == 'array' and !$schema->items) {
			self::error('array does not have any items defined');
		}
	}
	
	//TODO miks seda vaja on?
	public static function isMakeable(string $type): bool
	{
		return in_array($type, ['array', 'object']);
	}
	
	public static function isComponentSchema(string $ref): bool
	{
		return strpos($ref, '#/components/schemas/') !== false;
	}
	
	public static function isComponentResponse(string $ref): bool
	{
		return strpos($ref, '#/components/responses/') !== false;
	}
	
	public static function isComponentRequestBody(string $ref): bool
	{
		return strpos($ref, '#/components/requestBodies/') !== false;
	}
	
	public static function isComponentRef(string $ref): bool
	{
		return (self::isComponentResponse($ref) or self::isComponentSchema($ref) or self::isComponentRequestBody($ref));
	}
	
	public static function notImplementedYet()
	{
		Omg::error('this part of the code is not implemented yet');
	}
	
	public static function getLibPath(string $name = ''): string
	{
		return Config::getRootNamespace() . '\\lib' . ($name ? "\\$name" : '');
	}
	
	public static function getOperationPath(): string
	{
		if (Config::$operationClass) {
			return Config::$operationClass;
		}
		
		return self::getLibPath('Operation');
	}
	
	public static function error(string $msg)
	{
		throw new \Exception($msg);
	}
}