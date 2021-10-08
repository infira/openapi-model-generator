<?php

namespace Infira\Klahvik\console;


class Db extends Command
{
	protected ?string $namespace = 'db';
	protected ?string $name      = 'db';
	
	protected function downloadRemoteDb(string $db)
	{
		$this->info("dumping $db");
		$tmpPath = $this->remote->tmp();
		$this->remote->runKlahvikScript('dumpDb.sh', $db . ' "' . $tmpPath . '"');
		$this->info("downloading $db");
		$this->remote->rsync($this->remote->tmp('*.sql'), $this->local->tmp());
		$structurePath = $this->remote->klahvikPath("tmp/$db.structure.sql");
		$dataPath      = $this->remote->klahvikPath("tmp/$db.data.sql");
		$this->remote->execute([
			"rm -f $structurePath",
			"rm -f $dataPath",
		]);
	}
	
	protected function importVagrantDb(string $db, string $fromDb, bool $deleteDumpFiles = false)
	{
		if (empty(trim($this->vagrant->execute('sudo mysql -e \"SHOW DATABASES LIKE \'' . $db . '\'\"')->getOutput())))
		{
			$this->info("creating $db");
			$this->vagrant->execute('sudo mysql -e "CREATE DATABASE IF NOT EXISTS ' . $db . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"');
		}
		else
		{
			$this->info('droping local tables');
			$tmpPath = $this->vagrant->tmp();
			$this->vagrant->runKlahvikScript("dropLocalTables.sh", $db . ' "' . $tmpPath . '"');
		}
		$this->info('importing');
		$structureFile = $this->vagrant->tmp("$fromDb.structure.sql");
		$dataFile      = $this->vagrant->tmp("$fromDb.data.sql");
		$this->vagrant->execute([
			"sudo mysql $db < $structureFile",
			"sudo mysql $db < $dataFile",
		]);
		if ($deleteDumpFiles)
		{
			$this->vagrant->execute([
				"sudo rm -f $structureFile",
				"sudo rm -f $dataFile",
			]);
		}
	}
}