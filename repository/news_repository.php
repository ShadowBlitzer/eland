<?php

namespace repository;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class news_repository
{
	private $db;
	private $xdb;

	public function __construct(db $db, xdb $xdb)
	{
		$this->db = $db;
		$this->xdb = $xdb;
	}

	public function get_all(string $schema)
	{

	}

	public function get(int $id, string $schema):array
	{
		$data = $this->db->fetchAssoc('select *
			from ' . $schema . '.news 
			where id = ?', [$id]);
	
		if (!$data)
		{
			throw new NotFoundHttpException(sprintf(
				'News %d does not exist in %s', 
				$id, __CLASS__));
		}
		
		$row = $this->xdb->get('news_access', $id, $schema);

		if (!count($row))	
		{
			$row['data']['access'] = 'interlets';
			$this->xdb->set('news_access', $id, ['access' => 'interlets'], $schema);
		}

		$data['access'] = $row['data']['access'];	
			
		return $data;
	}

	public function insert(array $data, string $schema):int
	{
		$data['cdate'] = gmdate('Y-m-d H:i:s');
		$data['id_user'] = 1;//($s_master) ? 0 : $s_id;
		$data['approved'] = $data['approved'] ? 't' : 'f';
		$data['sticky'] = $data['sticky'] ? 't' : 'f';
		$data['published'] = 't';

		$access = $data['access'];
		unset($data['access']);
		
		$this->db->insert($schema . '.news', $data);
		$id = $this->db->lastInsertId($schema . '.news_id_seq');

		$this->xdb->set('news_access', $id, ['access' => $access], $schema);

		return $id;
	}

	public function update(int $id, array $data, string $schema)
	{
		$data['sticky'] = $data['sticky'] ? 't' : 'f';
		$data['approved'] = $data['approved'] ? 't' : 'f';
		$data['published'] = 't';
		$access = $data['access'];
		unset($data['access']);
		
		$this->db->update($schema . '.news', $data, ['id' => $id]);
		$this->xdb->set('news_access', $id, ['access' => $access], $schema);
	}

	public function approve(int $id, string $schema)
	{
		$this->db->update($schema . '.news', ['approved' => 't'], ['id' => $id]);
	}

	public function delete(int $id, string $schema)
	{
		$this->db->delete($schema . '.news', ['id' => $id]);
		$this->xdb->del('news_access', $id, $schema);
	}

	public function get_next(int $id, string $schema, array $access_ary)
	{
		$rows = $this->xdb->get_many([
			'agg_schema' 	=> $schema,
			'agg_type' 		=> 'news_access',
			'eland_id' 		=> ['>' => $id],
			'access' 		=> $access_ary,
		], 
		'order by eland_id asc limit 1');

		return count($rows) ? reset($rows)['eland_id'] : null;
	}

	public function get_prev(int $id, string $schema, array $access_ary)
	{
		$rows = $this->xdb->get_many([
			'agg_schema' => $schema,
			'agg_type' => 'news_access',
			'eland_id' => ['<' => $id],
			'access' => $access_ary,
		], 'order by eland_id desc limit 1');

		return count($rows) ? reset($rows)['eland_id'] : null;
	}


}
