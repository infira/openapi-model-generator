<?php

namespace Infira\omg;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Infira\omg\generator\ComponentResponse;
use Infira\omg\generator\PathRegister;
use Symfony\Component\Yaml\Yaml;
use Infira\omg\helper\Tpl;
use Infira\omg\generator\ComponentRequestBody;
use Infira\Utils\Dir;

class MainGenerator
{
	/**
	 * @var OpenApi
	 */
	protected $api;
	
	public function loadAPI(string $file = null)
	{
		if ($file === null)
		{
			$file = Config::$spec;
		}
		if (!is_file($file))
		{
			Omg::error('API file does not exists');
		}
		$path = pathinfo($file);
		
		switch (strtolower($path['extension']))
		{
			case 'yaml':
				$this->api = Reader::readFromYamlFile($file, OpenApi::class, false);
			break;
			
			case 'json':
				$this->api = Reader::readFromJsonFile($file, OpenApi::class, false);
			break;
			
			default:
				Omg::error('Unsupported API file');
			break;
		}
	}
	
	public function loadConfig(string $configFile)
	{
		if (!is_file($configFile))
		{
			Omg::error('Config file does not exists');
		}
		$path = pathinfo($configFile);
		
		if ($path['extension'] == 'yaml')
		{
			$config = Yaml::parseFile($configFile);
		}
		
		Config::load($config);
	}
	
	private function validate()
	{
		if (!$this->api)
		{
			Omg::error('API spec is not loaded');
		}
		if (!Config::isLoaded())
		{
			Omg::error('Config is not loaded');
		}
		$this->api->validate();
		debug($this->api->getErrors());
	}
	
	public function make()
	{
		$this->validate();
		
		$ns = Config::getRootNamespace() . '\\lib';
		Generator::makeFile('lib/RObject.php', Tpl::load('RObject.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		
		Generator::makeFile('lib/RArray.php', Tpl::load('RArray.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		
		Generator::makeFile('lib/Storage.php', Tpl::load('Storage.php', [
			'rootNamespace' => $ns,
			'namespace'     => 'namespace ' . $ns . ';',
		]));
		Generator::makeFile('lib/Operation.php', Tpl::load('Operation.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		
		/*
		Generator::makeFile('lib/Response.php', Tpl::load('Response.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		*/
		
		Dir::flush(Config::$destination);
		foreach ($this->api->components->schemas as $name => $schema)
		{
			Omg::validateSchema($schema);
			if (!Omg::isMakeable($schema->type))
			{
				continue;
			}
			$generator = Omg::getGenerator($schema->type, "/component/schema/$name", "#/components/schemas/$name", $schema);
			$generator->make();
		}
		
		foreach ($this->api->components->requestBodies as $name => $requestBody)
		{
			$componentResponseGenerator = new ComponentRequestBody($name);
			$componentResponseGenerator->make($requestBody);
		}
		
		foreach ($this->api->components->responses as $name => $response)
		{
			$componentResponseGenerator = new ComponentResponse($name);
			$componentResponseGenerator->make($response);
		}
		$pathRegisterGenerator = new PathRegister();
		$pathRegisterGenerator->make($this->api->paths);
	}
}