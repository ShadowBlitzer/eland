<?php declare(strict_types=1);

namespace service;

use Symfony\Component\HttpFoundation\Session\Session;
use exception\missing_parameter_exception;
use exception\invalid_parameter_value_exception;

class view
{
	private $default_ary = [
		'news'	=> 'extended',
		'user'	=> 'list',
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

		if (!isset($this->default_ary[$entity]))
		{
			throw new invalid_parameter_value_exception(sprintf('invalid "entity" %s in %s', $entity, __CLASS__));
		}		

		$key = $this->schema . '_' . $this->access . '_' . $entity . '_view';
	
		$get = $this->session->get($key);

		if (!isset($get))
		{
			$get = $this->default_ary[$entity];
			$this->session->set($key, $get);
		}

		return $get;
	}

	public function set(string $entity, string $view)
	{
		if (!$view || !$entity || !isset($this->default_ary[$entity]))
		{
			return;
		}

		$key = $this->schema . '_' . $this->access . '_' . $entity . '_view';
		
		$this->session->set($key, $view);
	}

	public function merge(array $param, string $entity = null):array
	{
		if (!isset($entity) || $entity === '')
		{
			return $param;
		}

		if (!isset($this->default_ary[$entity]))
		{
			return $param;
		}

		return array_merge($param, ['view' => $this->get($entity)]);		
	}

}
