<?php

namespace twig;

use service\groups;

class base_url
{
	protected $groups;
	protected $protocol;

	public function __construct(
		groups $groups,
		string $protocol
	)
	{
		$this->groups = $groups;
		$this->protocol = $protocol;
	}

	public function get(string $schema)
	{
		return $this->protocol . $this->groups->get_host($schema);
	}
}
