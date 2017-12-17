<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use exception\missing_parameter_exception;
use exception\invalid_parameter_value_exception;

class SessionColumns
{
	private $default_ary = [
		'news'	=> 'extended',
		'user'	=> [
			'a'	=> [
				'letscode', 'name', 'postcode', 'saldo',
			], 
			'i'	=> [
				'letscode', 'name', 'postcode', 'saldo',
			],
			'u'	=> [
				'letscode', 'name', 'postcode', 'saldo',
			]	
		],
		'ad'	=> 'extended',
	];

	private $session;
	private $schema;
	private $access;

	public function __construct(Session $session, string $schema, string $access)
	{
		$this->session = $session;
		$this->schema = $schema;
		$this->access = $access;
	}

	public function get(string $entity)
	{
		if (!isset($entity) || $entity === '')
		{
			throw new missing_parameter_exception(sprintf('"entity" is not set in %s', __CLASS__));
		}

		if (!isset($this->default_ary[$entity][$this->access]))
		{
			throw new invalid_parameter_value_exception(sprintf('invalid "entity" %s in %s', $entity, __CLASS__));
		}		

		$key = $this->schema . '_' . $this->access . '_' . $entity . '_columns';
	
		$get = $this->session->get($key);

		if (!isset($get))
		{
			$get = $this->default_ary[$entity][$access];
			$this->session->set($key, $get);
		}

		return $get;
	}

	public function set(string $entity, array $columns)
	{
		if (!$view || !$entity || !isset($this->default_ary[$entity]))
		{
			return;
		}

		$key = $this->schema . '_' . $this->access . '_' . $entity . '_columns';
		
		$this->session->set($key, $columns);
	}
}
