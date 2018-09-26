<?php

	if(is_file('../main.inc.php'))$dir = '../';
	else  if(is_file('../../../main.inc.php'))$dir = '../../../';
	else  if(is_file('../../../../main.inc.php'))$dir = '../../../../';
	else  if(is_file('../../../../../main.inc.php'))$dir = '../../../../../';
	else $dir = '../../';

	if(!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
		include($dir."master.inc.php");
	}
	elseif(!defined('INC_FROM_DOLIBARR')) {
		include($dir."main.inc.php");
	} else {
		global $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_pass;
	}
	if(!defined('DB_HOST') && !empty($dolibarr_main_db_host)) {
		define('DB_HOST',$dolibarr_main_db_host);
		define('DB_NAME',$dolibarr_main_db_name);
		define('DB_USER',$dolibarr_main_db_user);
		define('DB_PASS',$dolibarr_main_db_pass);
		define('DB_DRIVER',$dolibarr_main_db_type);
	}

	if(!dol_include_once('/abricot/inc.core.php')) exit('abricot');

	if(! defined('ATM_ASSET_NAME')) define('ATM_ASSET_NAME', (float) DOL_VERSION >= 8.0 || dol_is_dir(dol_buildpath('/assetatm')) ? 'assetatm' : 'asset');
	
	// Pour afficher la sélection d'un équipement par produit lors de l'ajout des lignes d'une commande
	//dolibarr_set_const($db, 'USE_ASSET_IN_ORDER', 1);
