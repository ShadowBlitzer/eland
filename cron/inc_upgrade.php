<?php
# Perform a DB update

function doupgrade($version)
{
	global $db;
	global $configuration;

	$db->beginTransaction();

	try{

		switch($version)
		{
			case 30000:
				break;

			case 30001:
				$query = "ALTER TABLE transactions ALTER COLUMN transid TYPE character varying(200)";
				exec($query);
				break;

			case 31000:
				$db->delete('letsgroups', array('id' => 0));
				break;

			case 31002:
				$query = "INSERT INTO config (category,setting,value,description,default) VALUES('system','ets_enabled','0', '', 0)";
				$db->insert('config', array(
					'category' 		=> 'system',
					'setting'		=> 'ets_enabled',
					'value'			=> '0',
					'description'	=> 'Enable ETS functionality',
					'default'		=> 0));
				break;

			case 31003:
				// FIXME: We need to repeat 2205 and 2206 to fix imported transactions after those updates
				break;
			default:

				break;
					
		}
		$db->update('parameters', array('value' => $version), array('parameter' => 'schemaversion'));
		$db->commit();
		return true;
	}
	catch(Exception $e)
	{
		$db->rollback();
		throw $e;
		return false;

	}
}
