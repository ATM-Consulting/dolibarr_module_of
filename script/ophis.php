<?php
require('../config.php');
require('../class/asset.class.php');
dol_include_once('/product/class/product.class.php');

global $db, $user;

function create_flacon_stock_mouvement(&$PDOdb, $fk_asset, $qte) {
	global $user, $langs;
	dol_include_once('/asset/class/asset.class.php');
	
	$asset = new TAsset;
	$asset->load($PDOdb,$fk_asset);
	$asset->contenancereel_value = $asset->contenancereel_value - $qte;
	$asset->save($PDOdb, $user, $langs->trans("InStockByOPHIS"));
}

function create_standard_stock_mouvement($fk_product, $id_entrepot, $qte, $subprice) {
	global $user, $langs, $db;
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

	$mouvS = new MouvementStock($db);
	// We decrement stock of product (and sub-products)
	// We use warehouse selected for each line
	$result=$mouvS->livraison($user, $fk_product, $id_entrepot, $qte, $subprice, $langs->trans("InStockByOPHIS"));
	return $result;
}

//On vérifie que nos deux paramètres d'entré sont bien transmis
if(isset($_REQUEST['ref']) && !empty($_REQUEST['ref']) && isset($_REQUEST['qte']) && !empty($_REQUEST['qte'])){
	
	$ref = $_REQUEST['ref'];
	$qte = $_REQUEST['qte'];
	
	$TPDOdb = new TPDOdb;
	
	//On vérifie si le produit existe dans dolibarr
	$TPDOdb->Execute("SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE ref = '".$ref."'");
	$TPDOdb->Get_line();
	
	if($TPDOdb->Get_field('rowid')){
		
		$product = new Product($db);
		$product->fetch($TPDOdb->Get_field('rowid'));
		
		//Récupération des flacons associé au produit
		$TPDOdb->Execute("SELECT rowid, contenancereel_value, contenance_value, contenancereel_units, contenance_units, lot_number, emplacement, serial_number
						  FROM ".MAIN_DB_PREFIX."asset WHERE fk_product = ".$product->id);
		
		$cpt = 0;
		while($TPDOdb->Get_line()){	
			$cpt++;
			
			//Calcule de la quantité pouvant être accueilli par le flacon
			$contenance = $TPDOdb->Get_field('contenance_value') * pow(10, ($TPDOdb->Get_field('contenance_units') - $TPDOdb->Get_field('contenancereel_units') ));
			$qte_max = $contenance - $TPDOdb->Get_field('contenancereel_value');
			
			if($qte_max >= $qte){
				
				$fk_asset = $TPDOdb->Get_field('rowid');
				$lot = $TPDOdb->Get_field('lot_number');
				$emplacement = $TPDOdb->Get_field('emplacement');
				$ref_falcon = $TPDOdb->Get_field('serial_number');
				
				//Récupération de l'entrepot dolibarr
				$id_entrepot = TRequeteCore::_get_id_by_sql($TPDOdb, "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot LIMIT 1");
				$id_entrepot = $id_entrepot[0];
				
				//Mouvements de stock
				create_flacon_stock_mouvement($TPDOdb,$fk_asset, $qte);
				create_standard_stock_mouvement($product->id, $id_entrepot, $qte, $product->price);
				
				$res = array(
						"lot" => $lot,
						"emplacement" => $emplacement,
						"ref" => $ref_falcon,
						"retour" => "Success : stock importé dans le flacon ".$ref_falcon." appartenant au lot ".$lot." et se trouvant à l'emplacement ".$emplacement
				);
				
				return json_encode($res);
			}
		}
		
		if($cpt == 0)
			return json_encode(array("retour" => "Error : aucun flacon ne peux accueillir la quantité souhaité."));
		else
			return json_encode(array("retour" => "Error : aucun flacon associé produit."));
	}
	else{
		return json_encode(array("retour" => "Error : aucun produit avec la référence ".$ref." trouvé dans dolibarr."));
	}
}
else{
	return json_encode(array("retour" => "Error : mauvais paramètres transmis."));
}
