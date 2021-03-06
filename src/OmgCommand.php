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
use Symfony\Component\Console\Input\InputArgument;

class OmgCommand extends \Infira\console\Command
{
	/**
	 * @var OpenApi
	 */
	protected $api;
	
	public function __construct()
	{
		parent::__construct('create');
	}
	
	public function configure(): void
	{
		$this->addArgument('config', InputArgument::REQUIRED);
	}
	
	/**
	 * @throws \Exception
	 */
	public function runCommand()
	{
		$configFile = $this->input->getArgument('config');
		if (!is_file($configFile)) {
			$this->error('Config file does not exists');
		}
		$path = pathinfo($configFile);
		
		if ($path['extension'] != 'yaml') {
			$this->error('Config file must be in yaml');
		}
		Config::load(Yaml::parseFile($configFile));
		
		$this->loadAPI();
		$this->validateAPI();
		$this->make();
	}
	
	private function loadAPI(string $file = null)
	{
		if ($file === null) {
			$file = Config::$spec;
		}
		if (!is_file($file)) {
			$this->error('API file does not exists');
		}
		$path = pathinfo($file);
		
		switch (strtolower($path['extension'])) {
			case 'yaml':
				$this->api = Reader::readFromYamlFile($file, OpenApi::class, false);
			break;
			
			case 'json':
				$this->api = Reader::readFromJsonFile($file, OpenApi::class, false);
			break;
			
			default:
				$this->error('Unsupported API file');
			break;
		}
	}
	
	private function validateAPI()
	{
		if (!$this->api) {
			$this->error('API spec is not loaded');
		}
		if (!Config::isLoaded()) {
			$this->error('Config is not loaded');
		}
		$this->api->validate();
		$this->output->region('Specis validation errors', function ()
		{
			$this->output->dumpArray($this->api->getErrors());
		});
	}
	
	private function make()
	{
		Dir::flush(Config::$destination);
		$ns = $this->getLibNs();
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
		
		$this->makeOperation();
		
		/*
		Generator::makeFile('lib/Response.php', Tpl::load('Response.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		*/
		
		foreach ($this->api->components->schemas as $name => $schema) {
			Omg::validateSchema($schema);
			if (!Omg::isMakeable($schema->type)) {
				continue;
			}
			$generator = Omg::getGenerator($schema->type, "/component/schema/$name", "#/components/schemas/$name", $schema);
			$generator->make();
		}
		
		foreach ($this->api->components->requestBodies as $name => $requestBody) {
			$componentResponseGenerator = new ComponentRequestBody($name);
			$componentResponseGenerator->make($requestBody);
		}
		
		foreach ($this->api->components->responses as $name => $response) {
			$componentResponseGenerator = new ComponentResponse($name);
			$componentResponseGenerator->make($response);
		}
		$pathRegisterGenerator = new PathRegister();
		$pathRegisterGenerator->make($this->api->paths);
	}
	
	private function getLibNs(): string
	{
		return Config::getRootNamespace() . '\\lib';
	}
	
	private function makeOperation()
	{
		$vars = [
			'namespace'                   => 'namespace ' . $this->getLibNs() . ';',
			'operationInputParameterName' => Config::$operationInputParameterName,
			'implements'                  => [],
			'laravel'                     => Config::$laravel,
		];
		if (Config::$laravel) {
			$vars['implements'] = ['\Illuminate\Contracts\Support\Renderable'];
		}
		
		$vars['implements'] = count($vars['implements']) > 0 ? ' implements ' . join(', ', $vars['implements']) : '';
		$template           = Config::$version == 1 ? 'Operation.tpl' : 'Operation.v2.tpl';
		Generator::makeFile('lib/Operation.php', Tpl::load($template, $vars));
	}
}