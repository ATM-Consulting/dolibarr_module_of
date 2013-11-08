<?php

define('INC_FROM_CRON_SCRIPT', true);
set_time_limit(0);
require('../config.php');
require('../lib/asset.lib.php');

//Interface qui renvoie les emprunts de ressources d'un utilisateur
$ATMdb=new TPDOdb;

$get = isset($_REQUEST['get'])?$_REQUEST['get']:'emprunt';

_get($ATMdb, $get);

function _get(&$ATMdb, $case) {
	switch (strtolower($case)) {
		case 'autocomplete':
			__out(_autocomplete($ATMdb,$_REQUEST['fieldcode'],$_REQUEST['term']));
			break;
	}
}

//Autocomplete sur les différents champs d'une ressource
function _autocomplete(&$ATMdb,$fieldcode,$value){
	$sql = "SELECT DISTINCT(".$fieldcode.")
			FROM ".MAIN_DB_PREFIX."rh_ressource
			WHERE ".$fieldcode." LIKE '".$value."%'
			ORDER BY ".$fieldcode." ASC"; //TODO Rajouté un filtre entité ?
	$ATMdb->Execute($sql);
	
	while ($ATMdb->Get_line()) {
		$TResult[] = $ATMdb->Get_field($fieldcode);
	}
	
	$ATMdb->close();
	return $TResult;
}
