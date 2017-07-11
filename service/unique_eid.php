<?php

namespace service;

use service\xdb;
use service\token;

class unique_eid
{
	private $xdb;
	private $token;

	public function __construct(xdb $xdb, token $token)
	{
		$this->xdb = $xdb;
		$this->token = $token;
	}

	/*
	 */

	public function get()
	{
		while(true)
		{
			$eid = $this->token->set_length(12)->gen();

			if ($this->xdb->free_eid_check($eid)
			{
				return $eid;
			}
		}
	}
}

