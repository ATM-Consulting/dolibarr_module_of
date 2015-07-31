<?php

define('INC_FROM_CRON_SCRIPT', true);
set_time_limit(0);
require('../config.php');

$PDOdb=new TPDOdb;

$sql = "SELECT aol.rowid, aol.lot_number FROM ".MAIN_DB_PREFIX."assetOf_line as aol WHERE 1";
$PDOdb->Execute($sql);
$Tres = $PDOdb->Get_All();

foreach ($Tres as $res) {
	
	$sql = "SELECT a.rowid FROM ".MAIN_DB_PREFIX."asset as a WHERE a.lot_number = '".$res->lot_number."' LIMIT 1";
	$PDOdb->Execute($sql);
	$TRes2 = $PDOdb->Get_All();
	
	foreach($TRes2 as $res2 ){
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source, fk_target, sourcetype, targettype)
				VALUES (".$res->rowid.",".$res2->rowid.",'TAssetOFLine','TAsset')";
		$PDOdb->Execute($sql);
	}
}