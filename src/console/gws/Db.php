<?php

namespace Infira\Klahvik\console\gws;

use Symfony\Component\Console\Input\InputArgument;

class Db extends \Infira\Klahvik\console\Db
{
	use RemoteConfig;
	
	protected ?string $name = 'gws';
	
	public function configure(): void
	{
		$this->addArgument('domain', InputArgument::REQUIRED);
		$this->addArgument('branch', InputArgument::REQUIRED);
		$this->addOption('local', 'l');
	}
	
	public function runCommand()
	{
		$domain    = $this->input->getArgument('domain');
		$branch    = $this->input->getArgument('branch');
		$localDB   = 'gws_' . $branch . '_' . $domain;
		$fromLocal = $this->input->getOption('local');
		
		$databases = [
			'garmin'      => 'd79590_lvgrm',
			'intra'       => 'd79590_livint',
			'gopro'       => 'd79590_lvgpr',
			'gps24'       => 'd79590_lvgps24',
			'gpseesti'    => 'd79590_lvgpe',
			'luxify'      => 'd79590_luxify',
			'meremaailm'  => 'd79590_lvmm',
			'miiego'      => 'd79590_miiego',
			'nutistuudio' => 'd79590_lvnut',
			'oakley'      => 'd79590_oakley',
		];
		
		if (!isset($databases[$domain]))
		{
			$this->error("domain $domain not found");
		}
		$liveDB = $databases[$domain];
		
		$structurePath = $this->local->tmp("$liveDB.structure.sql");
		$dataPath      = $this->local->tmp("$liveDB.data.sql");
		if (($fromLocal and (!file_exists($structurePath) or !file_exists($dataPath))) or !$fromLocal)
		{
			$this->section("downloading db($liveDB) from remote server", fn() => $this->downloadRemoteDb($liveDB));
		}
		$this->section("importing db($liveDB) to vagrant $localDB", function () use ($localDB, $liveDB, $fromLocal)
		{
			$this->importVagrantDb($localDB, $liveDB, !$fromLocal);
		});
	}
}