<?php

namespace twig;

class view_param
{
	public function get(array $param, string $entity = null):array
	{
		if (!isset($entity) || $entity === '')
		{
			return $param;
		}

		$ary = [
			'news'	=> 'extended',
			'user'	=> 'list',
			'ad'	=> 'extended',
		];

		if (!isset($ary[$entity]))
		{
			return $param;
		}

		return array_merge($param, ['view' => $ary[$entity]]);
	}
}
