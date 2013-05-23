<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/custom/asset/class/asset.class.php");

if(isset($_POST['fk_product'])){
	$id = $_POST['fk_product'];
}

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT DISTINCT(lot_number) AS lot
		FROM ".MAIN_DB_PREFIX."asset
		WHERE fk_product = ".$id."
		ORDER BY date_cre DESC";

$ATMdb->Execute($sql);

while($ATMdb->Get_line()){
	$Tres[] = array(
		"lot" => $ATMdb->Get_field('lot')
		,"lotAff" => "Lot n°".$ATMdb->Get_field('lot')
	);
}

echo json_encode($Tres);