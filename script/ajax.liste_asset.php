<?php
require("../config.php");
dol_include_once('/asset/class/asset.class.php');
dol_include_once('/core/lib/product.lib.php');

$langs->load('other');

/*if(isset($_REQUEST['fk_product'])){
	$id = $_REQUEST['fk_product'];
}
else {
	return false;
}*/
if(isset($_REQUEST['fk_soc'])){
	$socid = $_REQUEST['fk_soc'];
}
else {
	return false;
}

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT rowid, serial_number, lot_number, contenancereel_value, contenancereel_units
		FROM ".MAIN_DB_PREFIX."asset
		WHERE fk_soc = ".$socid."
		AND contenancereel_value > 0
		ORDER BY contenancereel_value DESC";
		
$ATMdb->Execute($sql);

while($ATMdb->Get_line()){
	$label = $ATMdb->Get_field('serial_number');
	$label.= " / Lot ".$ATMdb->Get_field('lot_number');
	$label.= " / ".number_format($ATMdb->Get_field('contenancereel_value'),2,",","")." ".measuring_units_string($ATMdb->Get_field('contenancereel_units'),"weight");
	
	$Tres[] = array(
		"flacon" => $ATMdb->Get_field('rowid')
		,"flaconAff" => $label
	);
}

echo json_encode($Tres);