<?php

namespace service;

use service\ev;
use service\token;

class unique_id
{
	private $ev;
	private $token;

	public function __construct(ev $ev, token $token)
	{
		$this->ev = $ev;
		$this->token = $token;
	}

	/*
	 */

	public function get()
	{
		while(true)
		{
			$id = $this->token->set_length(12)->gen();

			if (!$this->ev->id_in_use($id))
			{
				return $id;
			}
		}
	}
}

