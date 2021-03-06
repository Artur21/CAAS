<?php
//  Copyright 2013 Conix Security, Nicolas Correia, Adrien Chevalier
//
//  This file is part of CAAS.
//
//  CAAS is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  CAAS is distibuted in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with CAAS.  If not, see <http://www.gnu.org/licenses/>.

if(!defined("__INCLUDED__"))
	exit(0);

$db_handler = NULL;
$states=NULL;
$parse_metadata=NULL;
$autodownload_reports=NULL;
$kernelmode_score_medium=NULL;
$kernelmode_score_high=NULL;
$usermode_score_medium=NULL;
$usermode_score_high=NULL;
$enable_usermode_analysis=NULL;
$enable_kernelmode_analysis=NULL;
$usermode_timeout=NULL;
$kernelmode_timeout=NULL;
$sampling=NULL;
function open_db()
{
	global $db_handler;
	global $db_path;

	if(!file_exists($db_path))
	{
		error("[connect_database] Sqlite database file (".$db_path.") not found","CRITICAL");
	}

	$db_handler = new SQLite3($db_path,SQLITE3_OPEN_READONLY);
}
function close_db()
{
	global $db_handler;
	if($db_handler)
		$db_handler->close();
}
function open_db_write()
{
	global $db_handler;
	global $db_path;
	if(!file_exists($db_path))
	{
		error("[connect_database] Sqlite database file (".$db_path.") not found","CRITICAL");
	}
	$db_handler = new SQLite3($db_path);
}
function drop_write_db()
{
	close_db();
	open_db();
}
function get_write_db()
{
	close_db();
	open_db_write();
}
function query_db($request)
{
	global $db_handler;
	$result = $db_handler->query($request);
	if(!$result)
		error("[query_db] SQL error on request ".$request,"CRITICAL");
	return $result;
}
function init_config()
{
	global $db_handler;
	global $states,$parse_metadata,$autodownload_reports,$kernelmode_score_medium,$kernelmode_score_high,$usermode_score_medium,$usermode_score_high,$enable_usermode_analysis,$enable_kernelmode_analysis,$usermode_timeout,$kernelmode_timeout,$sampling;
	$result = query_db("SELECT parse_metadata, auto_download_reports, kernelmode_score_medium, kernelmode_score_high, usermode_score_medium, usermode_score_high, enable_usermode_analysis, enable_kernelmode_analysis, usermode_timeout, kernelmode_timeout, sampling FROM configuration LIMIT 0,1");
	$config = $result->fetchArray();
	if(!$config)
		error("[init_config] Empty configuration","CRITICAL");
	$parse_metadata = $config["parse_metadata"];
	$autodownload_reports = $config["auto_download_reports"];
	$usermode_score_medium = $config["usermode_score_medium"];
	$kernelmode_score_medium = $config["kernelmode_score_medium"];
	$usermode_score_high = $config["usermode_score_high"];
	$kernelmode_score_high = $config["kernelmode_score_high"];
	$enable_usermode_analysis = $config["enable_usermode_analysis"];
	$enable_kernelmode_analysis = $config["enable_kernelmode_analysis"];
	$usermode_timeout = $config["usermode_timeout"];
	$kernelmode_timeout = $config["kernelmode_timeout"];
	$sampling = $config["sampling"];
	
	$states = Array();
	$result = query_db("SELECT title FROM state_types ORDER BY state_type_id ASC");
	while($state = $result->fetchArray(SQLITE3_ASSOC))
	{
		$states[] = $state["title"];
	}
	if(empty($states))
	{
		$states[] = "not dispatched";
		$states[] = "running";
		$states[] = "finished";
		$states[] = "reported";
		$states[] = "failed";
		error("[init_config] Empty states","WARNING");
	}
	
}
function enable_local_source($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[enable_local_source] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE local_source set is_active = '1' where local_source_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function disable_local_source($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[disable_local_source] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE local_source set is_active = '0' where local_source_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function create_local_source($folder)
{
	global $db_handler;

	$folder_s = secure_sql($folder);
	if(!is_dir($folder_s))
	{
		error('[enable_local_source] Folder does not exists','ERROR');
		return 0;
	}
	$request = "INSERT INTO local_source(lookup_folder,is_active) VALUES('".$folder_s."','0');";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function enable_cuckoo_server($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[enable_cuckoo_server] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE cuckoo_server set is_active = '1' where cuckoo_server_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function disable_cuckoo_server($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[disable_cuckoo_server] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE cuckoo_server set is_active = '0' where cuckoo_server_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function enable_remote_source($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[enable_remote_source] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE remote_source set is_active = '1' where remote_source_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function disable_remote_source($id)
{
	global $db_handler;
	if(!is_numeric($id))
		error('[disable_remote_source] Not numeric parameter','SECURITY');
	$id_s = intval($id);
	$request = "UPDATE remote_source set is_active = '0' where remote_source_id = '".$id_s."'";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function create_remote_source($ip_addr)
{
	global $db_handler;
	$ip_addr_s =  secure_sql(filter_var($ip_addr, FILTER_VALIDATE_IP));
	if(!$ip_addr)
	{
		error('[enable_remote_source] Not numeric parameter','ERROR');
		return 0;
	}
	$request = "INSERT INTO remote_source(remote_ip_addr,is_active) VALUES('".$ip_addr_s."','0');";
	get_write_db();
	query_db($request);
	drop_write_db();
}
function update_main_config($parse_metadata_p,$autodownload_reports_p,$kernelmode_score_medium_p,$kernelmode_score_high_p,$usermode_score_medium_p,$usermode_score_high_p,$enable_usermode_analysis_p,$enable_kernelmode_analysis_p,$usermode_timeout_p,$kernelmode_timeout_p,$sampling_p = 100)
{
	global $db_handler;
	global $parse_metadata,$autodownload_reports,$kernelmode_score_medium,$kernelmode_score_high,$usermode_score_medium,$usermode_score_high,$enable_usermode_analysis,$enable_kernelmode_analysis,$usermode_timeout,$kernelmode_timeout,$sampling;
	
	if(!is_numeric($sampling_p) ||
		!is_numeric($kernelmode_score_medium_p) ||
		!is_numeric($kernelmode_score_high_p) ||
		!is_numeric($usermode_score_medium_p) ||
		!is_numeric($usermode_score_high_p) ||
		!is_numeric($kernelmode_timeout_p) ||
		!is_numeric($usermode_timeout_p) ||
		($autodownload_reports_p != 0 && $autodownload_reports_p != 1) ||
		($parse_metadata_p != 0 && $parse_metadata_p != 1) ||
		($enable_usermode_analysis_p != 0 && $enable_usermode_analysis_p != 1) ||
		($enable_kernelmode_analysis_p != 0 && $enable_kernelmode_analysis_p != 1)
	)
	{
		error('[update_main_config] Not numeric parameter','SECURITY');
	}
	
	$parse_metadata = intval($parse_metadata_p);
	$autodownload_reports = intval($autodownload_reports_p);
	$kernelmode_score_medium = intval($kernelmode_score_medium_p);
	$kernelmode_score_high = intval($kernelmode_score_high_p);
	$usermode_score_medium = intval($usermode_score_medium_p);
	$usermode_score_high = intval($usermode_score_high_p);
	$enable_usermode_analysis = intval($enable_usermode_analysis_p);
	$enable_kernelmode_analysis = intval($enable_kernelmode_analysis_p);
	$usermode_timeout = intval($usermode_timeout_p);
	$kernelmode_timeout = intval($kernelmode_timeout_p);
	$sampling = intval($sampling_p);

	$request = "UPDATE configuration SET
		parse_metadata = '$parse_metadata',
		auto_download_reports = '$autodownload_reports',
		kernelmode_score_medium = '$kernelmode_score_medium',
		kernelmode_score_high = '$kernelmode_score_high',
		usermode_score_medium = '$usermode_score_medium',
		usermode_score_high = '$usermode_score_high',
		enable_usermode_analysis = '$enable_usermode_analysis',
		enable_kernelmode_analysis = '$enable_kernelmode_analysis',
		usermode_timeout = '$usermode_timeout',
		kernelmode_timeout = '$kernelmode_timeout',
		sampling = '$sampling'";

	get_write_db();
	query_db($request);
	drop_write_db();

}
function init_db()
{
	open_db();
	init_config();	
}
function secure_sql($data)
{
	return addslashes($data);
}
function secure_sql_field($data)
{
	$len = strlen($data);
	$new = "";
	for($i = 0; $i < $len; $i++)
	{
		if( (ord($data[$i])>=0x30 && ord($data[$i])<= 0x39) ||
		(ord($data[$i])>=0x41 && ord($data[$i])<= 0x5A) ||
		(ord($data[$i])>=0x61 && ord($data[$i])<= 0x7A))
			$new.=$data[$i];
	}
	return $new;
}
function create_trigger($description,$label,$criticity,$field,$match,$type)
{
	if($label == '' || $criticity == '')
		return 0;

		$table_s = "";
                $description_s = secure_sql($description);
                $label_s = secure_sql_field($label);
                $criticity_s = secure_sql($criticity);
		$field_s = secure_sql($field);
		$match_s = secure_sql($match);
		if(!is_numeric($criticity_s))
			error('[create_trigger] Criticity not numeric "'.$criticity_s.'"','SECURITY');
		if($type == "std")
		{
			if($field_s == 'md5')
                        	$table_s = 'task';
                	elseif($field_s == 'sign')
                	{
                        	$table_s = 'signature';
                        	$field_s = 'title';
			}
                	else
                	{
                        	error('[create_trigger] Unknown field "'.$field_s.'"','SECURITY');
			}
			$request = "CREATE TRIGGER ".$label_s." AFTER INSERT ON ".$table_s." WHEN new.".$field_s." LIKE '%".$match_s."%' BEGIN INSERT INTO triggz(task_id, label, description, criticity) VALUES (new.task_id, '".$label_s."', '".$description_s."', '".$criticity_s."'); END;";
		}
		elseif($type == "meta")
		{
			$request = "CREATE TRIGGER ".$label_s." AFTER INSERT ON metadata WHEN new.name = '".$field_s."' AND new.value LIKE '%".$match_s."%' BEGIN INSERT INTO triggz(task_id, label, description, criticity) SELECT task_id, '".$label_s."', '".$description_s."', '".$criticity_s."' FROM submition WHERE new.submition_id = submition_id LIMIT 0,1; END;";
		}
		else
			error('[create_trigger] Unknown type','SECURITY');
		
		get_write_db();
		query_db($request);
		drop_write_db();
		return NULL;
}
function remove_trigger($name)
{
	global $db_handler;
	$name_s = secure_sql_field($name);
	$request = "DROP TRIGGER ".$name;
	get_write_db();
	query_db($request);
	drop_write_db();
	return NULL;
}
function get_triggerz()
{
	global $db_handler;
	$results = query_db("SELECT name,sql FROM sqlite_master WHERE type = 'trigger'");
	return $results;
}
function get_task_alerts($task_id)
{
	global $db_handler;
	$task_id_s = secure_sql($task_id);
	if(!is_numeric($task_id_s))
	{
		error("[get_task_alerts] Task ID not int: ".$task_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT triggz_id,task_id,label,description,criticity FROM triggz WHERE task_id = '".$task_id_s."' ORDER BY triggz_id DESC");
	return $results;
}
function get_metadata_names()
{
	$results = query_db("SELECT name FROM metadata GROUP BY name ORDER BY name");
	return $results;
}
function get_submition_metadata($sub_id)
{
	global $db_handler;
	$sub_id_s = secure_sql($sub_id);
	if(!is_numeric($sub_id_s))
	{
		error("[get_submition_metadata] Submition ID not int: ".$sub_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT metadata_id, name, value, submition_id FROM metadata where submition_id= '".$sub_id_s."' ORDER BY metadata_id");
	return $results;
}
function get_task_submitions($task_id)
{
	global $db_handler;
	$task_id_s = secure_sql($task_id);
	if(!is_numeric($task_id_s))
	{
		error("[get_task_submitions] Task ID not int: ".$task_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT submition_id, time, source_type, source_id, task_id FROM submition WHERE task_id = '".$task_id_s."' ORDER BY submition_id");
	return $results;	
}
function get_alertz($start,$count)
{
	global $db_handler;
	
	$start_s = intval($start);
	$count_s = intval($count);
	$limit = "";
	if($count_s != 0)
                $limit = " LIMIT ".$start_s.",".$count_s;

	$request = "SELECT triggz_id,task_id,label,description,criticity FROM triggz ORDER BY triggz_id DESC".$limit;
	$results = query_db($request);
	return $results;
}
function get_remote_source($source_id)
{
	global $db_handler;
	$source_id_s = secure_sql($source_id);
	if(!is_numeric($source_id))
	{
		error("[get_remote_source] ID not int: ".$source_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT remote_source_id,remote_ip_addr,is_active FROM remote_source WHERE remote_source_id = '".$source_id_s."' LIMIT 0,1");
	return $results;	
}
function get_local_source($source_id)
{
	global $db_handler;
	$source_id_s = secure_sql($source_id);
	if(!is_numeric($source_id))
	{
		error("[get_local_source] ID not int: ".$source_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT local_source_id,lookup_folder,is_active FROM local_source WHERE local_source_id = '".$source_id_s."' LIMIT 0,1");
	return $results;	
}
function get_source_type_title($source_type)
{
	global $db_handler;
	$source_type_s = secure_sql($source_type);
	if(!is_numeric($source_type))
	{
		error("[get_source_type_title] Type ID not int: ".$source_type_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT source_type_id,title FROM source_types WHERE source_type_id = '".$source_type_s."' LIMIT 0,1");
	return $results;	
}
function get_task_md5($task_id)
{
	global $db_handler;
	$task_id_s = secure_sql($task_id);
	if(!is_numeric($task_id))
	{
		error("[get_task_md5] Task ID not int: ".$task_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT md5 FROM task WHERE task_id = '".$task_id_s."' LIMIT 0,1");
	return $results;	
}
function get_remote_sources()
{
	global $db_handler;
	$results = query_db("SELECT remote_source_id,remote_ip_addr,is_active FROM remote_source ORDER BY is_active DESC, remote_ip_addr ASC");
	return $results;
}
function new_task($file_path)
{
	global $db_handler,$bin_path,$enable_usermode_analysis,$enable_kernelmode_analysis;
	if(!file_exists($file_path))
	{
		error("[new_task] File ".$file_path." does not exists","ERROR");
		return 0;
	}
	
	$md5 = md5_file($file_path);
	$new_path = $bin_path.$md5.".bin";
	if(!file_exists($new_path))
		if(!rename($file_path,$new_path))
		{
			error("[new_task] Could not rename file.","ERROR");
			return 0;
		}
	
	get_write_db();	
	$result = query_db("INSERT INTO task(md5) VALUES('".$md5."');");
	$task_id = $db_handler->lastInsertRowID();
	query_db("INSERT INTO submition(task_id,time,source_type,source_id) VALUES('".$task_id."','".time()."','3','0')");
	if($enable_usermode_analysis)
		query_db("INSERT INTO analysis(cuckoo_id,kernel_analysis,state,cuckoo_server_id,task_id) VALUES(0,0,0,0,'".$task_id."')");
	if($enable_kernelmode_analysis)
		query_db("INSERT INTO analysis(cuckoo_id,kernel_analysis,state,cuckoo_server_id,task_id) VALUES(0,1,0,0,'".$task_id."')");
	drop_write_db();
	
	return $task_id;
}
function get_local_sources()
{
	global $db_handler;
	$results = query_db("SELECT local_source_id,lookup_folder,is_active FROM local_source ORDER BY is_active DESC,lookup_folder ASC");
	return $results;
}
function get_cuckoo_servers()
{
	global $db_handler;
	$results = query_db("SELECT cuckoo_server_id,name,server_addr,ssh_port,username,cuckoo_path,is_active FROM cuckoo_server ORDER BY is_active DESC,server_addr ASC");
	return $results;
}
function get_alerts_count()
{
	global $db_handler;
	$result = query_db("SELECT count(triggz_id) as 'cpt' FROM triggz");
	$result = $result->fetchArray();
	return $result['cpt'];
}
function get_tasks_count()
{
	global $db_handler;
	$result = query_db("SELECT count(task_id) as 'cpt' FROM task");
	$result = $result->fetchArray();
	return $result['cpt'];
}
function get_tasks($start=0,$count=0)
{
	global $db_handler;
	$start_s = secure_sql($start);
	$count_s = secure_sql($count);
	if(!is_numeric($start) || !is_numeric($count))
	{
		error("[get_tasks] Param not int: start: ".$start_s." count: ".$count_s,"SECURITY");
		return NULL;
	}
	$limit = "";
	if($count_s != 0)
		$limit = " LIMIT ".$start_s.",".$count_s;
	$results = query_db("SELECT task_id, md5 FROM task ORDER BY task_id DESC".$limit);
	return $results;
}
function get_analysis_info($analysis_id)
{
	global $db_handler;
	$analysis_id_s = secure_sql($analysis_id);
	if(!is_numeric($analysis_id))
	{
		error("[get_analysis_info] Analysis ID not int: ".$analysis_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT a.analysis_id,cuckoo_id,kernel_analysis,state,SUM(s.score) as 'total_score',cuckoo_server_id,a.task_id,md5 FROM analysis a, task t, (SELECT signature_id,analysis_id,score FROM signature UNION SELECT 0,".$analysis_id_s.",0) s WHERE a.analysis_id = '".$analysis_id_s."' AND a.task_id = t.task_id AND s.analysis_id = a.analysis_id GROUP BY a.analysis_id LIMIT 0,1");
	return $results;	
}
function get_matched_signatures($analysis_id)
{
	global $db_handler;
	$analysis_id_s = secure_sql($analysis_id);
	if(!is_numeric($analysis_id))
	{
		error("[get_metched_signatures] Analysis ID not int: ".$analysis_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT signature_id,title,score,analysis_id FROM signature WHERE analysis_id = '".$analysis_id_s."' ORDER BY score ASC");
	return $results;
}
function get_cuckoo_server_info($server_id)
{
	global $db_handler;
	$server_id_s = secure_sql($server_id);
	if(!is_numeric($server_id))
	{
		error("[get_cuckoo_server_info] Server ID not int: ".$server_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT cuckoo_server_id,name,server_addr,ssh_port,username,password,cuckoo_path,vms_count,is_active FROM cuckoo_server WHERE cuckoo_server_id = '".$server_id_s."' LIMIT 0,1");
	return $results;	

}
function get_task_analyses($task_id)
{
	global $db_handler;
	$task_id_s = secure_sql($task_id);
	if(!is_numeric($task_id))
	{
		error("[get_task_analyses] Task ID not int: ".$task_id_s,"SECURITY");
		return NULL;
	}
	$results = query_db("SELECT a.analysis_id,kernel_analysis,SUM(s.score) AS 'total_score',state FROM analysis a,(SELECT signature_id,analysis_id,score FROM signature UNION SELECT 0,analysis_id,0 FROM analysis) s WHERE task_id = '".$task_id_s."' AND s.analysis_id = a.analysis_id GROUP BY a.analysis_id ORDER BY a.analysis_id ASC");
	return $results;	

}
?>
