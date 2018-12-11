<?php

namespace App\Legacy\queue;

interface queue_interface
{
	public function process(array $data);
	public function queue(array $data);
}
