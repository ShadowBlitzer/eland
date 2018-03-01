<?php

namespace App\Service;

use App\Repository\ConfigRepository;

class Config
{
	private $configRepository;

	public function __construct(ConfigRepository $configRepository)
	{
		$this->configRepository = $configRepository;
	}

	public function get(string $key, string $schema):string 
	{
		return $this->configRepository->get($key, $schema);
	}
}
