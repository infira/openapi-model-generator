<?php

namespace Infira\Klahvik\helper;

use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;
use Infira\Utils\Dir;
use Infira\Klahvik\console\Command;

class Server
{
	private ?string $klahvikPath;
	private string  $user;
	private string  $host;
	private ?int    $port;
	private Command $cmd;
	
	public function __construct(Command $cmd, string $user, string $host, int $port = null)
	{
		$this->cmd  = $cmd;
		$this->user = $user;
		$this->host = $host;
		$this->port = $port;
	}
	
	public function setKlahvikPath(string $klahvikPath): void
	{
		$this->klahvikPath = Dir::fixPath($klahvikPath);
	}
	
	public function klahvikPath(string $path = ''): string
	{
		if (str_contains($path, '*'))
		{
			return $this->klahvikPath . $path;
		}
		
		return Dir::fixPath($this->klahvikPath . $path);
	}
	
	private function ssh(): Ssh
	{
		return Ssh::create($this->user, $this->host, $this->port);
	}
	
	/**
	 * @param string|array $command
	 * @return \Symfony\Component\Process\Process
	 */
	public function execute($command, callable $outputCallback = null): Process
	{
		$ssh = $this->ssh();
		if ($outputCallback)
		{
			$ssh->onOutput(fn($type, $line) => $outputCallback($line));
		}
		
		return $ssh->execute($command);
	}
	
	public function runKlahvikScript(string $script, string $arguments = '', callable $outputCallback = null)
	{
		$arguments = $arguments ?: " $arguments";
		$bashPath  = $this->klahvikPath('bash');
		
		$this->execute([
			"cd $bashPath",
			"bash $script $arguments",
		], fn($line) => $this->say($line));
	}
	
	public function downloadFromKlahvik(string $filePattern, string $destination)
	{
		$this->rsync($this->klahvikPath($filePattern), $destination);
	}
	
	public function importDb(string $sourceDb, string $fromDb, bool $sudo = false)
	{
		$structureFile = $this->klahvikPath("tmp/$fromDb.structure.sql");
		$dataFile      = $this->klahvikPath("tmp/$fromDb.data.sql");
		$mysql         = $sudo ?: "sudo mysql" . "mysql";
		$this->execute([
			"$mysql $sourceDb < $structureFile",
			"$mysql $sourceDb < $dataFile",
		]);
	}
	
	public function rsync(string $src, string $destination)
	{
		$process = Process::fromShellCommandline("rsync -av --progress --del $this->user@$this->host:$src $destination");
		$process->run(fn($type, $line) => $this->say($line));
	}
	
	//region helpers
	private function say(string $msg)
	{
		$this->cmd->say($msg, 'remote');
	}
	//endregion
}