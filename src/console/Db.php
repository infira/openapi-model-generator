<?php

namespace Infira\Klahvik\console;


use Infira\Utils\File;

class Db extends Command
{
	protected ?string $namespace = 'db';
	protected ?string $name      = 'db';
	
	/**
	 * @param string $db
	 * @param bool   $whenNotExists Download only when dump file doest not exists
	 */
	protected function downloadRemoteDb(string $db, bool $whenNotExists = false)
	{
		$this->info("dumping $db");
		$this->remote->runKlahvikScript('dumpDb.sh', $db);
		$this->info("downloading $db");
		$this->remote->downloadFromKlahvik('tmp/*.sql', $this->local->klahvikPath('tmp/'));
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
			$this->vagrant->runKlahvikScript("dropLocalTables.sh", $db);
		}
		$this->info('importing');
		$this->vagrant->importDb($db, $fromDb, true);
		if ($deleteDumpFiles)
		{
			File::delete($this->local->klahvikPath("tmp/$db.structure.sql"));
			File::delete($this->local->klahvikPath("tmp/$db.data.sql"));
		}
	}
}