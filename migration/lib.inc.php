<?php

	// Set Timezone as server time
	if(version_compare(PHP_VERSION, '5.6.0') >= 0)
	{
		date_default_timezone_set(@date_default_timezone_get());
	}

    /**
     * DB의 정보를 구하는 함수 (대상 tool마다 다름)
     * db에 접속할 수 있도록 정보를 구한 후 형식을 맞춰 zMigration에서 쓸수 있도록 return
     **/
    function getDBInfo($path) {
        if(substr($path,-1)=='/') $path = substr($path, 0, strlen($path)-1);
        $config_file = sprintf('%s/files/config/db.config.php',$path);

        define('__ZBXE__',true);
        define('__XE__',true);

        if(!file_exists($config_file)) return;
        include($config_file);

        $info = new stdClass();
        $info->db_type = $db_info->master_db['db_type'];
        $info->db_port = $db_info->master_db['db_port'];
        $info->db_hostname = $db_info->master_db['db_hostname'];
        $info->db_userid = $db_info->master_db['db_userid'];
        $info->db_password = $db_info->master_db['db_password'];
        $info->db_database = $db_info->master_db['db_database'];
        $info->db_table_prefix = $db_info->master_db['db_table_prefix'];

		if(substr($info->db_table_prefix, -1) == '_') $info->db_table_prefix = substr($info->db_table_prefix, 0, -1);

        return $info;
    } 

?>
