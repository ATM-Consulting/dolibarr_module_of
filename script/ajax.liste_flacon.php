<?php
require("../config.php");
dol_include_once('/asset/class/asset.class.php');
dol_include_once('/core/lib/product.lib.php');

$langs->load('other');

if(isset($_POST['fk_product'])){
	$id = $_POST['fk_product'];
}

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT rowid, serial_number, lot_number, contenancereel_value, contenancereel_units, emplacement
		FROM ".MAIN_DB_PREFIX."asset
		WHERE fk_product = ".$id."
		ORDER BY contenancereel_value DESC";
		
$ATMdb->Execute($sql);

while($ATMdb->Get_line()){
	$label = $ATMdb->Get_field('serial_number');
	$label.= " / Batch ".$ATMdb->Get_field('lot_number')." / Stock ".$ATMdb->Get_field('emplacement');
	$label.= " / ".number_format($ATMdb->Get_field('contenancereel_value'),2,",","")." ".measuring_units_string($ATMdb->Get_field('contenancereel_units'),"weight");
	
	$Tres[] = array(
		"flacon" => $ATMdb->Get_field('rowid')
		,"flaconAff" => $label
	);
}

echo json_encode($Tres);