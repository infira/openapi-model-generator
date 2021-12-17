<?php
ini_set('memory_limit', '3G');

use Infira\Error\Handler;
use Infira\omg\MainGenerator;

require_once '../vendor/autoload.php';

$Handler = new Handler(["errorLevel"           => -1,//-1 means all erors, see https://www.php.net/manual/en/function.error-reporting.php
                        "env"                  => "dev", //dev,stable (stable env does not display full errors erros
                        "debugBacktraceOption" => DEBUG_BACKTRACE_IGNORE_ARGS]);

try
{
	system('rm -rf /var/www/git/gitHubInfira/openapi-model-generator/test/generated/*');
	$gen = new MainGenerator();
	$gen->loadConfig('config.yaml');
	$gen->loadAPI("/var/www/git/Tellitoit/apiHtdocs/versions/master/public/swagger.json");
	$gen->make();
	system('rm -rf /var/www/git/Tellitoit/apiHtdocs/versions/master/app/apiSpec/*');
	system('cp -R /var/www/git/gitHubInfira/openapi-model-generator/test/generated/* /var/www/git/Tellitoit/apiHtdocs/versions/master/app/apiSpec/');
}
catch (\Infira\Error\Error $e)
{
	echo $e->getHTMLTable();
}
catch (Throwable $e)
{
	$ie = $Handler->catch($e);
	echo $ie->getHTMLTable();
}