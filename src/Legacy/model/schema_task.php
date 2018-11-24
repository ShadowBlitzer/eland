<?php

namespace App\Legacy\model;

use App\Legacy\model\task_interface;
use App\Legacy\model\task;

use App\Legacy\service\schedule;
use App\Legacy\service\groups;
use App\Legacy\service\this_group;

abstract class schema_task extends task implements task_interface
{
	protected $groups;
	protected $this_group;
	protected $schema;

	public function __construct(schedule $schedule, groups $groups, this_group $this_group)
	{
		parent::__construct($schedule);

		$this->groups = $groups;
		$this->this_group = $this_group;
	}

	public function should_run()
	{
		$this->schedule->set_time();

		foreach ($this->groups->get_schemas() as $schema)
		{
			$this->schema = $schema;

			if (!$this->is_enabled())
			{
				continue;
			}

			$should_run = $this->schedule->set_id($this->schema . '_' . $this->get_class_name())
				->set_interval($this->get_interval())
				->should_run();

			if ($should_run)
			{
				return true;
			}
		}

		return false;
	}

	public function run()
	{
		$this->this_group->force($this->schema);
		parent::run();
	}
}
