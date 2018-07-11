<?php declare(strict_types=1);

namespace migrate_elas;

interface base_interface
{
	public function set_schema(string $schema);

	public function execute();

	public function get_step();
}
