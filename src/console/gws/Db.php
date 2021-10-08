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
			'garmin' => 'd79590_lvgrm',
			'intra'  => 'd79590_livint',
		];
		
		/*
		domainLiveDb[intra]=d79590_livint
		domainLocalDb[intra]=gws_NAME_intra
		
		domainLiveDb[garmin]=d79590_lvgrm
		domainLocalDb[garmin]=gws_NAME_garmin
		
		domainLiveDb[gopro]=d79590_lvgpr
		domainLocalDb[gopro]=gws_NAME_gopro
		
		domainLiveDb[gps24]=d79590_lvgps24
		domainLocalDb[gps24]=gws_NAME_gps24
		
		domainLiveDb[gpseesti]=d79590_luxify
		domainLocalDb[gpseesti]=gws_NAME_luxify
		
		domainLiveDb[gpseesti]=d79590_lvgpe
		domainLocalDb[gpseesti]=gws_NAME_gpseesti
		
		domainLiveDb[meremaailm]=d79590_lvmm
		domainLocalDb[meremaailm]=gws_NAME_meremaailm
		
		domainLiveDb[miiego]=d79590_miiego
		domainLocalDb[miiego]=gws_NAME_miiego
		
		domainLiveDb[nutistuudio]=d79590_lvnut
		domainLocalDb[nutistuudio]=gws_NAME_nutistuudio
		
		domainLiveDb[oakley]=d79590_oakley
		domainLocalDb[oakley]=gws_NAME_oakley
		 */
		
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