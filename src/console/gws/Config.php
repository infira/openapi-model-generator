<?php

namespace Infira\Klahvik\console\gws;

/**
 * @mixin \Infira\Klahvik\console\Command
 */
trait Config
{
	public function configServers(): void
	{
		$this->setRemoteServer('virt79333', 'DN-68-92.TLL01.ZONEAS.EU', '/data01/virt79333/domeenid/www.garmineesti.ee/klahvik');
		$this->setVagrantServer('vagrant', '192.168.33.10', '/var/www/git/gitHubInfira/klahvik');
	}
}