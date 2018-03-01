<?php

namespace App\Service;

use Doctrine\DBAL\Connection as Db;

/*
                                        Table "xdb.queue"
  Column  |            Type             |                           Modifiers
----------+-----------------------------+----------------------------------------------------------------
 ts       | timestamp without time zone | default timezone('utc'::text, now())
 data     | jsonb                       |
 topic    | character varying(60)       | not null
 priority | integer                     | default 0
 id       | bigint                      | not null default nextval('xdb.queue_id_seq'::regclass)
Indexes:
    "queue_pkey" PRIMARY KEY, btree (id)
    "queue_id_priority_idx" btree (id, priority)

*/

class Queue
{
	private $db;

	public function __construct(Db $db)
	{
		$this->db = $db;
	}

	public function set(string $topic, array $data, int $priority = 0):Queue
	{
		$insert = [
			'topic'			=> $topic,
			'data'			=> json_encode($data),
			'priority'		=> $priority,
		];

		$this->db->insert('xdb.queue', $insert);

		return $this;
	}

	public function get(string $topic):array
	{
		$row = $this->db->fetchAssoc('select topic, data, id, priority
				from xdb.queue
				where topic = ?
				order by priority desc, id asc
				limit 1', [$topic]);

		if (is_array($row))
		{
			$this->db->delete('xdb.queue', ['id' => $row['id']]);

			error_log('delete from queue id: ' . $row['id'] . ', topic: ' . 
				$row['topic'] . ', priority: ' . $row['priority'] . ', data: ' . 
				$row['data']);

			return json_decode($row['data'], true);
		}

		return [];
	}

	public function count(string $topic):int
	{
		if ($topic)
		{
			return $this->db->fetchColumn('select count(*)
				from xdb.queue
				where topic = ?', [$topic]);
		}

		return $this->db->fetchColumn('select count(*) from xdb.queue');
	}
}
