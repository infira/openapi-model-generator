<?php

namespace Infira\Klahvik\console\gws;

class Data extends \Infira\Klahvik\console\Data
{
	use RemoteConfig;
	
	protected ?string $name = 'gws';
	
	public function __construct()
	{
		parent::__construct();
		$this->setSyncDefaultPath('/data01/virt79333/domeenid/www.garmineesti.ee/www/cms/DATA/*', '/Volumes/X5/ws/projectData/gwsData/');
	}
}