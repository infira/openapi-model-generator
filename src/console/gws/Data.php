<?php

namespace Infira\Klahvik\console\gws;

class Data extends \Infira\Klahvik\console\Data
{
	use Config;
	
	protected ?string $name = 'gws';
	
	public function configure(): void
	{
		parent::configure();
		$this->setSyncDefaultPath('/data01/virt79333/domeenid/www.garmineesti.ee/www/cms/DATA/*', '/Volumes/X5/ws/projectData/gwsData/');
	}
}