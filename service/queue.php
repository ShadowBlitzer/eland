<?php

namespace service;

use Doctrine\DBAL\Connection as db;

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

class queue
{
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;
	}

	/*
	 *
	 */

	public function set(string $topic, array $data, int $priority = 1000)
	{
		$insert = [
			'topic'			=> $topic,
			'data'			=> json_encode($data),
			'priority'		=> $priority,
		];

		try
		{
			$this->db->insert('xdb.queue', $insert);
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			throw $e;
			exit;
		}
	}

	/*
	 *
	 */

	public function get(array $topic_ary)
	{
		$sql_where = $sql_params = $sql_types = [];

		if (count($topic_ary))
		{
			$sql_where[] = 'topic in (?)';
			$sql_params[] = $topic_ary;
			$sql_types[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
		}

		$sql_where = count($sql_where) ? ' where ' . implode(' and ', $sql_where) : '';

		$query = 'select topic, data, id, priority
				from xdb.queue
				' . $sql_where . '
				order by priority desc, id asc
				limit 1';

		try
		{
			$stmt = $this->db->executeQuery($query, $sql_params, $sql_types);

			if ($row = $stmt->fetch())
			{
				$return = [
					'data'		=> json_decode($row['data'], true),
					'id'		=> $row['id'],
					'topic'		=> $row['topic'],
					'priority'	=> $row['priority'],
				];

				error_log('delete: ' . $row['id'] . ' : ' . 
					$this->db->delete('xdb.queue', ['id' => $row['id']]));

				return $return;
			}

		}
		catch (\Exception $e)
		{
			error_log('err queue: ' . $e->getMessage());

			throw $e;
		}

		return [];
	}

	/**
	 *
	 */

	public function count(string $topic)
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

