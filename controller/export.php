<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use exception\missing_function_exception;

class export
{
	private $elas_csv_ary = [
		'users'		=> [
			'sql'		=> 'select * from %schema%.users order by letscode',
			'columns'	=> [
				'letscode',
				'cdate',
				'comments',
				'hobbies',
				'name',
				'postcode',
				'login',
				'mailinglist',
				'password',
				'accountrole',
				'status',
				'lastlogin',
				'minlimit',
				'maxlimit',
				'fullname',
				'admincomment',
				'adate',
			],
		],
		'contacts'	=> [
			'sql'	=> 'select c.*, tc.abbrev, u.letscode, u.name
				from %schema%.contact c, %schema%.type_contact tc, %schema%.users u
				where c.id_type_contact = tc.id
					and c.id_user = u.id',
			'columns'	=> [
				'letscode',
				'username',
				'abbrev',
				'comments',
				'value',
				'flag_public',
			],
		],
		'categories'	=> [
			'sql'		=> 'select * from %schema%.categories',
			'columns'	=> [
				'name',
				'id_parent',
				'description',
				'cdate',
				'fullname',
				'leafnote',
			],
		],
		'messages'	=> [
			'sql'		=> 'select m.*, u.name as username, u.letscode
				from %schema%.messages m, %schema%.users u
				where m.id_user = u.id
					and validity > ?',
			'sql_bind'	=> ['%gmdate%'],
			'columns'	=> [
				'letscode',
				'username',
				'cdate',
				'validity',
				'content',
				'msg_type',
			],
		],
		'transactions'	=> [
			'sql'		=> 'select t.transid, t.description,
								concat(fu.letscode, \' \', fu.name) as from_user,
								concat(tu.letscode, \' \', tu.name) as to_user,
								t.cdate, t.real_from, t.real_to, t.amount
							from %schema%.transactions t, %schema%.users fu, %schema%.users tu
							where t.id_to = tu.id
								and t.id_from = fu.id
							order by t.date desc',
			'columns'	=> [
				'cdate',
				'from_user',
				'real_from',
				'to_user',
				'real_to',
				'amount',
				'description',
				'transid',
			],
		],
	];

	private $r = "\r\n";

	public function index(Request $request, app $app, string $schema)
	{
		set_time_limit(60);

		$elas_db_form = $app->namedForm('elas_db', [], [
			'etoken_enabled'	=> false,	
		])
			->add('submit', SubmitType::class, [
				'label'	=> 'export.elas.db.btn_label',
			])
			->getForm()
			->handleRequest($request);

		if ($elas_db_form->isValid())
		{
			if (!function_exists('exec'))
			{
				throw new missing_function_exception(
					sprintf('function "exec" does not exist 
						in class %s', __CLASS__));
			}
			
			$filename = $schema . '-elas-db-' . gmdate('Y-m-d-H-i-s') . '-' . substr(sha1(microtime()), 0, 8) . '.sql';
			
			exec('pg_dump --dbname=' . getenv('DATABASE_URL') . 
				' --schema=' . $schema . 
				' --no-owner --no-acl > ' . $filename);
			
			$out = '';
			
			$handle = fopen($filename, 'rb');
				
			if (!$handle)
			{
				exit;
			}
	
			while (!feof($handle))
			{
				$out .= fread($handle, 8192);
			}
				
			fclose($handle);
	
			unlink($filename);	
			
			return New Response($out, Response::HTTP_OK, [
				'Content-Type' 					=> 'application/force-download',
				'Content-disposition'			=> 'attachment; filename=' . $filename,
				'Content-Transfer-Encoding'		=> 'binary',
				'Pragma'						=> 'no-cache',
				'Expires'						=> '0',
			]);
		}

		$elas_csv_form_views = [];

		$gmdate = gmdate('Y-m-d H:i:s');

		foreach ($this->elas_csv_ary as $table => $table_data)
		{
			$form = $app->namedForm('elas_csv_' . $table, [], [
				'etoken_enabled'	=> false,	
			])
				->add('submit', SubmitType::class, [
					'label'		=> 'export.elas.csv.' . $table . '.btn_label',
				])
				->getForm()
				->handleRequest($request);
				
			if ($form->isValid())
			{
				$query = str_replace('%schema%', $schema, $table_data['sql']);

				$sql_bind = $table_data['sql_bind'] ?? [];

				if (count($sql_bind))
				{
					foreach ($sql_bind as &$val)
					{
						$val = str_replace('%gmdate', $gmdate, $val);
					}
				}

				$data = $app['db']->fetchAll($query, $sql_bind);
			
				$fields = $columns = [];

				foreach($table_data['columns'] as $column)
				{
					$translated = $app->trans('export.elas.csv.' . $table . '.column.' . $column);
					$fields[] = strpos($translated, 'export.elas.csv.') === 0 ? $column : $translated;	
					$columns[] = $column;
				}
			
				$out = '"' . implode('","', $fields) . '"' . $this->r;
		
				foreach($data as $row)
				{
					$fields = [];
		
					foreach($columns as $c)
					{
						$fields[] = $row[$c] ?? '';
					}
		
					$out .= '"' . implode('","', $fields) . '"' . $this->r;
				}

				$filename = 'elas-' . $table . '-' . gmdate('Y-m-d-H-i-S').'.csv';

				return new Response($out, Response::HTTP_OK, [
					'Content-Type' 					=> 'application/force-download',
					'Content-disposition'			=> 'attachment; filename=' . $filename,
					'Content-Transfer-Encoding'		=> 'binary',
					'Pragma'						=> 'no-cache',
					'Expires'						=> '0',
				]);
			}

			$elas_csv_form_views[] = $form->createView();
		}

		$elas_csv_forms = [];

		return $app['twig']->render('export/a_index.html.twig',[
			'elas_db_form'			=> $elas_db_form->createView(),
			'elas_csv_form_views'	=> $elas_csv_form_views,
		]);
	}
}
