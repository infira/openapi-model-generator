<?php

namespace Infira\Klahvik\console\gws;

/**
 * @mixin \Infira\Klahvik\console\Command
 */
trait RemoteConfig
{
	public function configureRemote(): void
	{
		$this->opt('REMOTE_USER', 'virt79333');
		$this->opt('REMOTE_HOST', 'DN-68-92.TLL01.ZONEAS.EU');
		$this->opt('REMOTE_KLAHVIK_PATH', '/data01/virt79333/domeenid/www.garmineesti.ee/klahvik');
		$this->opt('REMOTE_TMP_PATH', '/data01/virt79333/domeenid/www.garmineesti.ee/klahvik/tmp');
	}
}