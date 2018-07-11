<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use exception\missing_parameter_exception;
use exception\invalid_parameter_value_exception;

class SessionView
{
	private $defaultAry = [
		'news'	=> 'extended',
		'user'	=> 'list',
		'ad'	=> 'extended',
	];

	private $session;

	public function __construct(SessionInterface $session)
	{
		$this->session = $session;
	}

	public function get(string $entity, string $schema, string $access):string
	{
		if (!isset($entity) || $entity === '')
		{
			throw new missing_parameter_exception(sprintf('"entity" is not set in %s', __CLASS__));
		}

		if (!isset($this->defaultAry[$entity]))
		{
			throw new invalid_parameter_value_exception(sprintf('invalid "entity" %s in %s', $entity, __CLASS__));
		}

		$key = $schema . '_' . $access . '_' . $entity . '_view';
	
		$get = $this->session->get($key);

		if (!isset($get))
		{
			$get = $this->defaultAry[$entity];
			$this->session->set($key, $get);
		}

		return $get;
	}

	public function set(string $entity, string $schema, string $access, string $view)
	{
		if (!$view || !$entity || !isset($this->defaultAry[$entity]))
		{
			return;
		}

		$key = $schema . '_' . $access . '_' . $entity . '_view';
		
		$this->session->set($key, $view);
	}

	public function merge(array $params, string $entity):array
	{
		if (!isset($this->defaultAry[$entity]))
		{
			return $params;
		}

		return array_merge($params, ['view' => $this->get($entity, $params['schema'], $params['access'])]);		
	}

}
