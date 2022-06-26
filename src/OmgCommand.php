<?php

namespace Infira\omg;

use cebe\openapi\Reader;
use cebe\openapi\spec\{OpenApi, Paths, Reference, RequestBody, Response as ResponseSepc};
use Infira\omg\generator\ComponentRequestBody;
use Infira\omg\generator\ComponentResponse;
use Infira\omg\generator\PathRegister;
use Infira\omg\helper\Tpl;
use Infira\omg\helper\Utils;
use Infira\omg\templates\libs\Operation;
use Infira\omg\templates\libs\Response;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Wolo\File\Folder;
use Wolo\File\Path;

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
			Omg::error('Config file does not exists');
		}
		$path = pathinfo($configFile);
		
		if ($path['extension'] != 'yaml') {
			Omg::error('Config file must be in yaml');
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
			Omg::error("API file('$file') does not exists");
		}
		$file = realpath($file);
		$path = pathinfo($file);
		
		switch (strtolower($path['extension'])) {
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
	
	private function validateAPI()
	{
		if (!$this->api) {
			Omg::error('API spec is not loaded');
		}
		if (!Config::isLoaded()) {
			Omg::error('Config is not loaded');
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
		Folder::delete(Path::join(Config::$destination, 'components'));
		Folder::delete(Path::join(Config::$destination, 'lib'));
		Folder::delete(Path::join(Config::$destination, 'path'));
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
		$this->makeComponentSchemas($this->api->components->schemas);
		$this->makeComponentRequestBodies($this->api->components->requestBodies);
		$this->makeComponentResponses($this->api->components->responses);
		$this->makePathRegister($this->api->paths);
	}
	
	/**
	 * @param \cebe\openapi\spec\Schema[] $schemas
	 * @return void
	 */
	private function makeComponentSchemas(array $schemas)
	{
		foreach ($schemas as $name => $schema) {
			Omg::validateSchema($schema);
			if (!Omg::isMakeable($schema->type)) {
				continue;
			}
			Omg::getGenerator($schema, "/components/schema/$name", "#/components/schemas/$name", $schema->type)->make();
		}
	}
	
	/**
	 * @param RequestBody[]|Reference[] $requests
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	private function makeComponentRequestBodies(array $requests)
	{
		foreach ($requests as $name => $request) {
			Omg::getGenerator($request->content[Omg::getContentType($request)]->schema, "/components/requestBodies/$name", "#/components/requestBodies/$name")->make();
		}
	}
	
	/**
	 * @param ResponseSepc[] $responses
	 * @return void
	 */
	private function makeComponentResponses(array $responses)
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