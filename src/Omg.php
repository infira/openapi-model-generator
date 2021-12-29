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
			if (!self::isComponentRef($bodySchema->getReference())) {
				self::notImplementedYet();
			}
			/*
			if (self::isComponentSchema($bodySchema->getReference())) {
				$generator->setSchema($bodySchema->resolve());
			}
			*/
			$generator->tpl->setExtends(self::getReferenceClassPath($bodySchema->getReference()));
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
		return in_array($type, ['array', 'object']);
	}
	
	public static function isComponentHeader(string $ref): bool
	{
		return strpos($ref, '#/components/headers/') !== false;
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
	
	public static function getReferenceClassPath(string $ref): string
	{
		if (self::isComponentResponse($ref)) {
			$type = 'response';
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
		
		return '\\' . Utils::ns()->get('/components', $type, ucfirst(Utils::extractName($ref)));
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
		elseif ($resource instanceof Schema and !isset($schema->properties)) {
			return 'object';
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
	
	public static function error(string $msg)
	{
		throw new \Exception($msg);
	}
}