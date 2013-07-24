<?php
require("../config.php");
require(DOL_DOCUMENT_ROOT."/custom/asset/class/asset.class.php");

if(isset($_POST['fk_product'])){
	$id = $_POST['fk_product'];
}

function _unit($unite){
	switch ($unite) {
		case -9:
			return 'µg';
			break;
		case -6:
			return 'mg';
			break;
		case -3:
			return 'g';
			break;
		case -3:
			return 'gr';
			break;
		case 0:
			return 'kg';
			break;
	}
}

$ATMdb = new Tdb;
$Tres = array();

$sql = "SELECT DISTINCT(lot_number) AS lot, MIN(contenancereel_units) as min_unit
		FROM ".MAIN_DB_PREFIX."asset
		WHERE fk_product = ".$id."
		ORDER BY date_cre DESC";

$ATMdb->Execute($sql);

while($ATMdb->Get_line()){
	
	$sql = "SELECT contenancereel_value, contenancereel_units
			FROM ".MAIN_DB_PREFIX."asset
			WHERE lot_number = \"".$ATMdb->Get_field('lot')."\"";
			
	$ATMdb2 = new Tdb;
	$ATMdb2->Execute($sql);
	
	$total_reel = 0;
	while($ATMdb2->Get_line()){
		$total_reel += $ATMdb2->Get_field('contenancereel_value') * pow(10,($ATMdb2->Get_field('contenancereel_units') - $ATMdb->Get_field('min_unit')));
	}
	$ATMdb2->close();
	
	$sql = 'SELECT cd.poids as unite, cd.tarif_poids as poids
			FROM '.MAIN_DB_PREFIX.'commandedet as cd
				LEFT JOIN '.MAIN_DB_PREFIX.'commande as c ON (c.rowid = cd.fk_commande)
			WHERE asset_lot = "'.$ATMdb->Get_field('lot').'"
				AND c.fk_statut > 0';
	
	$ATMdb2 = new Tdb;				
	$ATMdb2->Execute($sql);
	
	$total_theorique = 0;
	while($ATMdb2->Get_line()){
		$total_theorique += $ATMdb2->Get_field('poids') * pow(10,($ATMdb2->Get_field('unite') - $ATMdb->Get_field('min_unit')));
	}
	$ATMdb2->close();
	
	$Tres[] = array(
		"lot" => $ATMdb->Get_field('lot')
		,"lotAff" => $ATMdb->Get_field('lot')." - ".$total_reel." "._unit($ATMdb->Get_field('min_unit'))." (".($total_reel - $total_theorique).")"
	);
}

echo json_encode($Tres);