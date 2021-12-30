<?php

namespace Infira\omg;

use cebe\openapi\Reader;
use Symfony\Component\Yaml\Yaml;
use Infira\omg\helper\Tpl;
use Infira\omg\generator\ComponentRequestBody;
use Infira\Utils\Dir;
use Symfony\Component\Console\Input\InputArgument;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\templates\libs\Operation;
use cebe\openapi\spec\{Paths, Reference, Response as ResponseSepc, RequestBody, OpenApi};
use Infira\omg\generator\PathRegister;
use Infira\omg\templates\libs\Response;
use Infira\omg\generator\ComponentResponse;
use Infira\omg\helper\Utils;

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
		$this->output->region('Specs validation warnings/errors', function ()
		{
			$this->output->dumpArray($this->api->getErrors());
		});
	}
	
	/**
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 */
	private function make()
	{
		Dir::flush(Config::$destination);
		$ns = Omg::getLibPath();
		Generator::makeFile('lib/RObject.php', Tpl::load('libs/RObject.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		
		Generator::makeFile('lib/RArray.php', Tpl::load('libs/RArray.php', [
			'namespace' => 'namespace ' . $ns . ';',
		]));
		
		Generator::makeFile('lib/Storage.php', Tpl::load('libs/Storage.php', [
			'rootNamespace' => $ns,
			'namespace'     => 'namespace ' . $ns . ';',
		]));
		
		$this->makeOperation();
		$this->makeLibResponse();
		
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
			$generator = Omg::getGenerator($schema, "/components/schema/$name", "#/components/schemas/$name", $schema->type);
			$generator->make();
		}
		
		$this->makeComponentRequestBodies($this->api->components->requestBodies);
		$this->makeComponentResponse($this->api->components->responses);
		$this->makePathRegister($this->api->paths);
	}
	
	/**
	 * @param RequestBody[]|Reference[] $requests
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	private function makeComponentRequestBodies(array $requests)
	{
		foreach ($requests as $name => $request) {
			$componentResponseGenerator = Omg::getGenerator($request->content[Omg::getContentType($request)]->schema, "/components/requestBodies/$name", "/components/requestBodies/$name");
			$propertiesAreMandatory     = Config::$mandatoryResponseProperties ? 'true' : 'false';
			$componentResponseGenerator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
			$componentResponseGenerator->make();
		}
		
	}
	
	/**
	 * @param ResponseSepc[] $responses
	 * @return void
	 */
	private function makeComponentResponse(array $responses)
	{
		foreach ($responses as $name => $response) {
			$generator = new ComponentResponse($name, $response);
			$generator->make();
		}
	}
	
	private function makePathRegister(Paths $paths)
	{
		$generator = new PathRegister($paths);
		$generator->make();
	}
	
	private function makeOperation()
	{
		$nss          = Omg::getLibPath();
		$pf           = new PhpFile();
		$ns           = $pf->addNamespace($nss);
		$classPhpType = $pf->addClass("$nss\Operation");
		$op           = new Operation($classPhpType, $ns);
		$op->finalize();
		
		Generator::makeFile('lib/Operation.php', Utils::printNette($pf));
	}
	
	private function makeLibResponse()
	{
		$nss          = Omg::getLibPath();
		$pf           = new PhpFile();
		$ns           = $pf->addNamespace($nss);
		$classPhpType = $pf->addClass("$nss\Response");
		$res          = new Response($classPhpType, $ns);
		$res->finalize();
		
		Generator::makeFile('lib/Response.php', Utils::printNette($pf));
	}
	
}