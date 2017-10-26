<?php

namespace service;

use exception\missing_parameter_exception;
use exception\invalid_parameter_value_exception;

class view
{
	private $ary = [
		'news'	=> 'extended',
		'user'	=> 'list',
		'ad'	=> 'extended',
	];

	public function __construct()
	{
	}

	public function get(string $entity)
	{
		if (!isset($entity) || $entity === '')
		{
			throw new missing_parameter_exception(sprintf('"entity" is not set in %s', __CLASS__));
		}

		if (!isset($this->ary[$entity]))
		{
			throw new invalid_parameter_value_exception(sprintf('invalid "entity" %s in %s', $entity, __CLASS__));
		}		

		return $this->ary[$entity];
	}

	public function merge(array $param, string $entity = null):array
	{
		if (!isset($entity) || $entity === '')
		{
			return $param;
		}

		if (!isset($this->ary[$entity]))
		{
			return $param;
		}

		return array_merge($param, ['view' => $this->ary[$entity]]);		
	}

}
