<?php

namespace Infira\omg;

use cebe\openapi\spec\Schema;
use Infira\omg\generator\SchemaObjectGenerator;
use Infira\omg\generator\SchemaArrayGenerator;
use cebe\openapi\spec\Reference;
use Infira\omg\generator\SchemaBlankGenerator;
use Infira\omg\helper\Utils;
use cebe\openapi\spec\Response;

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
	 * @param Reference|Schema $bodySchema
	 * @param string           $namespace
	 * @param string           $schemaLocation
	 * @param string|null      $type if null it will be autodetect
	 * @throws \Exception
	 * @return SchemaArrayGenerator|SchemaBlankGenerator|SchemaObjectGenerator
	 */
	public static function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		if ($bodySchema and !($bodySchema instanceof Reference) and !($bodySchema instanceof Schema)) {
			self::error('$bodySchema must be Reference or Schema ' . get_class($bodySchema) . ' was given');
		}
		$type = $type ?: self::getType($bodySchema);
		
		if ($type == 'object') {
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
			self::validateSchema($bodySchema);
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
	
	//TODO miks seda vaja on?
	public static function isMakeable(string $type): bool
	{
		if (!$type)
		{
			return false;
		}
		return in_array($type, ['array', 'object']);
	}
	
	public static function isComponentHeader(Reference $ref): bool
	{
		return strpos($ref->getReference(), '#/components/headers/') !== false;
	}
	
	public static function isComponentSchema(Reference $ref): bool
	{
		return strpos($ref->getReference(), '#/components/schemas/') !== false;
	}
	
	public static function isComponentResponse(Reference $ref): bool
	{
		return strpos($ref->getReference(), '#/components/responses/') !== false;
	}
	
	public static function isComponentRequestBody(Reference $ref): bool
	{
		return strpos($ref->getReference(), '#/components/requestBodies/') !== false;
	}
	
	public static function isMakeableReference(Reference $ref): bool
	{
		return self::isMakeable(self::getType($ref));
	}
	
	public static function isComponent(Reference $ref): bool
	{
		return (self::isComponentResponse($ref) or self::isComponentSchema($ref) or self::isComponentRequestBody($ref));
	}
	
	public static function getReferenceClassnameSuffix(Reference $ref): string
	{
		if (self::isComponentResponse($ref)) {
			return 'Response';
		}
		elseif (self::isComponentSchema($ref)) {
			return 'Schema';
		}
		elseif (self::isComponentRequestBody($ref)) {
			return 'RequestBody';
		}
		
		return '';
	}
	
	public static function getReferenceClassPath(Reference $ref): string
	{
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
			self::error('unknown reference');
		}
		
		return '\\' . Utils::ns()->get('/components', $type, ucfirst(Utils::extractName($ref->getReference()) . self::getReferenceClassnameSuffix($ref)));
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
	 * @param Reference|Response|Schema $resource
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return string
	 */
	public static function getType($resource): string
	{
		if ($resource instanceof Reference) {
			return self::getType($resource->resolve());
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
		throw new \Exception($msg);
	}
	
	public static function debug(...$data)
	{
		self::$console->debug(...$data);
	}
	
	public static function trace(string $regionTitle = 'omg trace')
	{
		self::$console->traceRegion($regionTitle, debug_backtrace());
	}
}