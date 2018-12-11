<?php

namespace task;

use Predis\Client as Redis;
use Doctrine\DBAL\Connection as db;
use Psr\Log\LoggerInterface;
use App\Legacy\service\cache;
use App\Legacy\service\s3;
use App\Legacy\service\groups;

class cleanup_images
{
	protected $days = 365;
	protected $cache;
	protected $db;
	protected $monolog;
	protected $s3;
	protected $groups;

	public function __construct(
		cache $cache,
		db $db,
		LoggerInterface $monolog,
		s3 $s3,
		groups $groups
	)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->monolog = $monolog;
		$this->s3 = $s3;
		$this->groups = $groups;
	}

	public function process():void
	{
		// $schema is not used, files of all schemas are scanned

		$cached = $this->cache->get('cleanup_image_files_marker');

		$marker = $cached['marker'] ?? '0';

		$time_treshold = time() - (84600 * $this->days);

		$object = $this->s3->find_img($marker);

		if (!$object)
		{
			$this->cache->set('cleanup_image_files_marker', ['marker' => '0']);
			error_log('-- no image file found --- reset marker --');
			return;
		}

		$this->cache->set('cleanup_image_files_marker',
			['marker' => $object['Key']]);

		$object_time = strtotime($object['LastModified'] . ' UTC');

		$old = $object_time < $time_treshold;

		$str_log = $object['Key'] . ' ' . $object['LastModified'] . ' ';
		$str_log .= $old ? 'OLD' : 'NEW';

		error_log($str_log);

		if (!$old)
		{
			return;
		}

		[$sch, $type, $id, $hash] = explode('_', $object['Key']);

		if (!$this->groups->get_host($sch))
		{
			error_log('-> unknown schema. ' . $sch . ' (no delete)');
			return;
		}

		if (!$this->table_exists('users', $sch))
		{
			error_log('-> table not present for schema ' .
				$sch . '.users (no delete)');
			return;
		}

		if (!$this->table_exists('msgpictures', $sch))
		{
			error_log('-> table not present for schema ' .
				$sch . '.msgpictures (no delete)');
			return;
		}

		if (!in_array($type, ['u', 'm']))
		{
			error_log('-> unknown type u, m (no delete)');
			return;
		}

		if ($type == 'u' && ctype_digit((string) $id))
		{
			$user = $this->db->fetchAssoc('select id, "PictureFile"
				from ' . $sch . '.users
				where id = ?', [$id]);

			if (!$user)
			{
				$del_str = '->User does not exist.';
			}
			else if ($user['PictureFile'] != $object['Key'])
			{
				$del_str = '->does not match db key ' . $user['PictureFile'];
			}
			else
			{
				error_log(' user image is still present in db ');
				return;
			}
		}
		else if ($type == 'm' && ctype_digit((string) $id))
		{
			$msgpict = $this->db->fetchAssoc('select *
				from ' . $sch . '.msgpictures
				where msgid = ?
					and "PictureFile" = ?', [$id, $object['Key']]);

			if (!$msgpict)
			{
				$del_str = '->is not present in db.';
			}
			else
			{
				error_log(' msg image is still present in db ');
				return;
			}
		}

		error_log(' -- delete img --');
		$this->s3->img_del($object['Key']);

		if ($del_str)
		{
			$this->monolog->info('(cron) image file ' . $object['Key'] .
				' deleted ' . $del_str, ['schema' => $sch]);
		}
	}

	protected function table_exists(string $table, string $schema):bool
	{
		return $this->db->fetchColumn('
			select 1
			from   pg_catalog.pg_class c
			join   pg_catalog.pg_namespace n ON n.oid = c.relnamespace
			where  n.nspname = \'' . $schema . '\'
			and    c.relname = \'' . $table . '\'
			and    c.relkind = \'r\'') ? true : false;
	}
}