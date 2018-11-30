<?php

namespace App\Legacy\schema_task;

use Doctrine\DBAL\Connection as db;
use Psr\Log\LoggerInterface;

use App\Legacy\model\schema_task;
use App\Legacy\service\xdb;
use App\Legacy\service\schedule;
use App\Legacy\service\groups;
use App\Legacy\service\this_group;

class cleanup_news extends schema_task
{
	private $db;
	private $xdb;
	private $monolog;

	public function __construct(
		db $db,
		xdb $xdb,
		LoggerInterface $monolog,
		schedule $schedule,
		groups $groups,
		this_group $this_group
	)
	{
		parent::__construct($schedule, $groups, $this_group);
		$this->db = $db;
		$this->xdb = $xdb;
		$this->monolog = $monolog;
	}

	public function process()
	{
		$now = gmdate('Y-m-d H:i:s');

		$news = $this->db->fetchAll('select id, headline from ' . $this->schema . '.news where itemdate < ? and sticky = \'f\'', [$now]);

		foreach ($news as $n)
		{
			$this->xdb->del('news_access', $n['id'], $this->schema);
			$this->db->delete($this->schema . '.news', ['id' => $n['id']]);
			$this->monolog->info('removed news item ' . $n['headline'],
				['schema' => $this->schema]);
		}
	}

	public function get_interval()
	{
		return 86400;
	}
}
