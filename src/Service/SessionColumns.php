<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use exception\missing_parameter_exception;
use exception\invalid_parameter_value_exception;

class SessionColumns
{
	private $defaultAry = [
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

	public function __construct(SessionInterface $session)
	{
		$this->session = $session;
	}

	public function get(string $entity, string $schema, string $access):array
	{
		if (!isset($entity) || $entity === '')
		{
			throw new missing_parameter_exception(sprintf('"entity" is not set in %s', __CLASS__));
		}

		if (!isset($this->defaultAry[$entity]))
		{
			throw new invalid_parameter_value_exception(sprintf('invalid "entity" %s in %s', $entity, __CLASS__));
		}		

		$key = $schema . '_' . $access . '_' . $entity . '_columns';
	
		$get = $this->session->get($key);

		if (!isset($get))
		{
			$get = $this->defaultAry[$entity][$access];   // TODO
			$this->session->set($key, $get);
		}

		return $get;
	}

	public function set(string $entity, string $schema, string $access, array $columns)
	{
		if (!$view || !$entity || !isset($this->defaultAry[$entity]))
		{
			return;
		}

		$key = $schema . '_' . $access . '_' . $entity . '_columns';
		
		$this->session->set($key, $columns);
	}
}
