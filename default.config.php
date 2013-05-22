<?php

	define('ROOT','/var/www/ATM/dolibarr/htdocs/');
	define('COREROOT','/var/www/ATM/atm-core/');
	define('COREHTTP','http://127.0.0.1/ATM/atm-core/');

	define('HTTP','http://127.0.0.1/ATM/dolibarr/');

	if(defined('INC_FROM_CRON_SCRIPT')) {
		include(ROOT."master.inc.php");	
	}
	else {
		include(ROOT."main.inc.php");
	}

	define('DB_HOST',$dolibarr_main_db_host);
	define('DB_NAME',$dolibarr_main_db_name);
	define('DB_USER',$dolibarr_main_db_user);
	define('DB_PASS',$dolibarr_main_db_pass);
	define('DB_DRIVER','mysqli');

	define('DOL_PACKAGE',true);
	define('USE_TBS',true);
	
	define('ASSET_FICHE_TPL','*****.fiche.tpl.php');

	require(COREROOT.'inc.core.php');