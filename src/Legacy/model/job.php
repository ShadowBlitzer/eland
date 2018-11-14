<?php

namespace App\Legacy\model;

abstract class job
{
	public function get_class_name()
	{
		$full = static::class;
		$pos = strrpos($full, '\\');

		if ($pos)
		{
			return substr($full, $pos + 1);
		}

		return $full;
	}
}
