<?php

namespace App\Service;

class XdbAccess
{
	private $accessAry = [
		'a'	=> ['admin', 'users', 'interlets'],
		'u' => ['users', 'interlets'],
		'i' => ['interlets'],
		'g' => ['interlets'],
	];	

	public function __construct()
	{
	}

	public function get(string $access):array
	{
		return $this->accessAry[$access] ?? [];
	}

}
