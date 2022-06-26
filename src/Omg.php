<?php

namespace Infira\omg;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Infira\console\Console;
use Infira\omg\generator\SchemaArrayGenerator;
use Infira\omg\generator\SchemaBlankGenerator;
use Infira\omg\generator\SchemaObjectGenerator;
use Infira\omg\generator\SchemaRequestParameterObjectGenerator;
use Infira\omg\helper\ParametersSpec;
use Infira\omg\helper\Utils;

class Omg
{
	private static $generatedItems = [];
	
	/**
	 * @var \Infira\console\ConsoleOutput $console
	 */
	public static $console;
	
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
	 * @param Reference|Schema|ParametersSpec $bodySchema
	 * @param string                          $namespace
	 * @param string                          $schemaLocation
	 * @param string|null                     $type if null it will be autodetect
	 * @throws \Exception
	 * @return SchemaArrayGenerator|SchemaBlankGenerator|SchemaObjectGenerator
	 */
	public static function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		if ($bodySchema and !($bodySchema instanceof Reference) and !($bodySchema instanceof Schema) and !($bodySchema instanceof ParametersSpec)) {
			self::error('$bodySchema must be Reference or Schema ' . get_class($bodySchema) . ' was given');
		}
		$type = $type ?: self::getType($bodySchema);
		
		if ($type == 'requestParameterObject') {
			$generator = new SchemaRequestParameterObjectGenerator($namespace, $schemaLocation);
		}
		elseif ($type == 'object') {
			$generator = new SchemaObjectGenerator($namespace, $schemaLocation);
		}
		elseif ($type == 'array') {
			$generator = new SchemaArrayGenerator($namespace, $schemaLocation);
		}
		else {
			$generator = new SchemaBlankGenerator($namespace, $schemaLocation);
		}
		
		if ($bodySchema and $bodySchema instanceof Reference) {
			if (!self::isComponent($bodySchema)) {
				self::notImplementedYet();
			}
			/*
			if (self::isComponentSchema($bodySchema->getReference())) {
				$generator->setSchema($bodySchema->resolve());
			}
			*/
			$generator->tpl->setExtends(self::getReferenceClassPath($bodySchema));
		}
		elseif ($bodySchema !== null) {
			$generator->setSchema($bodySchema);
		}
		
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
	
	/**
	 * @param string|Reference $ref
	 * @return string
	 */
	public static function getRef($ref): string
	{
		return is_object($ref) ? $ref->getReference() : $ref;
	}
	
	/**
	 * @param string|Reference $type
	 * @return bool
	 */
	public static function isMakeable($type): bool
	{
		if (!$type) {
			return false;
		}
		if (is_object($type))// and $type instanceof Reference)
		{
			$type = self::getType($type);
		}
		
		return in_array($type, ['array', 'object']);
	}
	
	/**
	 * @param string|Reference $ref
	 * @return bool
	 */
	public static function isComponentHeader($ref): bool
	{
		return strpos(self::getRef($ref), '#/components/headers/') !== false;
	}
	
	/**
	 * @param string|Reference $ref
	 * @return bool
	 */
	public static function isComponentSchema($ref): bool
	{
		return strpos(self::getRef($ref), '#/components/schemas/') !== false;
	}
	
	/**
	 * @param string|Reference $ref
	 * @return bool
	 */
	public static function isComponentResponse($ref): bool
	{
		return strpos(self::getRef($ref), '#/components/responses/') !== false;
	}
	
	/**
	 * @param string|Reference $ref
	 * @return bool
	 */
	public static function isComponentRequestBody($ref): bool
	{
		return strpos(self::getRef($ref), '#/components/requestBodies/') !== false;
	}
	
	/**
	 * @param string|Reference $ref
	 * @return bool
	 */
	public static function isComponent($ref): bool
	{
		return (self::isComponentResponse($ref) or self::isComponentSchema($ref) or self::isComponentRequestBody($ref));
	}
	
	/**
	 * @param string|Reference $ref
	 * @return string
	 */
	public static function getClassnameSuffix($ref): string
	{
		if (preg_match('/^\#\/components\/responses\/\w+$/m', $ref)) {
			return 'Response';
		}
		elseif (preg_match('/^\#\/components\/schemas\/\w+$/m', $ref)) {
			return 'Schema';
		}
		elseif (preg_match('/^\#\/components\/requestBodies\/\w+$/m', $ref)) {
			return 'RequestBody';
		}
		
		return '';
	}
	
	/**
	 * @param string|Reference $ref
	 * @throws \Exception
	 * @return string
	 */
	public static function getReferenceClassPath($ref): string
	{
		$ref = self::getRef($ref);
		if (self::isComponentResponse($ref)) {
			$type = 'responses';
		}
		elseif (self::isComponentSchema($ref)) {
			$type = 'schema';
		}
		elseif (self::isComponentRequestBody($ref)) {
			$type = 'requestBodies';
		}
		else {
			self::error("unknown reference('$ref')");
		}
		
		return '\\' . Utils::ns()->get('/components', $type, ucfirst(Utils::extractName(self::getRef($ref)) . self::getClassnameSuffix($ref)));
	}
	
	public static function notImplementedYet()
	{
		self::error('this part of the code is not implemented yet');
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
	
	/**
	 * @param Reference|Response $resource
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return string
	 */
	public static function getContentType($resource): string
	{
		if ($resource instanceof Reference) {
			return self::getContentType($resource->resolve());
		}
		else//if ($resource instanceof Response)
		{
			return array_keys((array)$resource->content)[0];
		}
	}
	
	/**
	 * @param Reference|Response|Schema|ParametersSpec $resource
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return string
	 */
	public static function getType($resource): string
	{
		if ($resource instanceof Reference) {
			return self::getType($resource->resolve());
		}
		elseif ($resource instanceof ParametersSpec) {
			return 'requestParameterObject';
		}
		elseif ($resource instanceof Schema) {
			if (isset($resource->type)) {
				return $resource->type;
			}
			if (isset($resource->items)) {
				return 'array';
			}
			if (isset($resource->properties)) {
				return 'object';
			}
			addExtraErrorInfo('schema', $resource);
			self::error('unknown type');
			
		}
		else//if ($resource instanceof Response)
		{
			//addExtraErrorInfo('$resource', $resource);
			return $resource->type;
		}
	}
	
	public static function getComponentResponseContentNsPart(): string
	{
		return "../content/%className%Content";
	}
	
	public static function error(string $msg, array $extraData = [])
	{
		if ($extraData) {
			addExtraErrorInfo($extraData);
		}
		Console::error($msg, $extraData);
	}
	
	public static function debug(...$data)
	{
		Console::debug(...$data);
	}
	
	public static function trace(string $regionTitle = 'omg trace')
	{
		Console::traceRegion($regionTitle, debug_backtrace());
	}
}