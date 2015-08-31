<?php

define('INC_FROM_CRON_SCRIPT', true);
set_time_limit(0);
require('../config.php');

dol_include_once('/product/class/product.class.php');
dol_include_once('/asset/class/asset.class.php');

$PDOdb=new TPDOdb;

$user = new User($db);
$user->fetch(1);

/*$sql = "SELECT aol.rowid, aol.lot_number FROM ".MAIN_DB_PREFIX."assetOf_line as aol WHERE 1";
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
}*/

//On prends tous les produits finis pour les changer d'entrepôt

if(GETPOST('TransfertStock')){
	echo '<hr>CHANGEMENT D\'ENTREPOT DES PRODUITS FINIS<hr>';
	
	$sql = "SELECT p.rowid 
			FROM ".MAIN_DB_PREFIX."product as p
			WHERE p.finished = 1 AND stock > 0 AND fk_product_type = 0";
	
	$PDOdb->Execute($sql);
	
	while ($res = $PDOdb->Get_line()) {
		$product = new Product($db);
		$product->fetch($res->rowid);
		$product->load_stock();
		
		// Remove stock
		$result1=$product->correct_stock(
			$user,
			1,
			$product->stock_reel,
			1,
			"Transfert de stock ".date('d/m/Y H:i'),
			$product->stock_warehouse[1]->pmp
		);
	
		if($result1) echo $product->label." Transfert de stock ".date('d/m/Y H:i')." ---> RETRAIT<br>";
		else{
			echo "ERREUR RETRAIT"; break;
		} 
		
		// Add stock
		$result2=$product->correct_stock(
			$user,
			2,
			$product->stock_reel,
			0,
			"Transfert de stock ".date('d/m/Y H:i'),
			$product->stock_warehouse[1]->pmp
		);
	
		if($result2) echo $product->label." Transfert de stock ".date('d/m/Y H:i')." ---> AJOUT<br>";
		else{
			echo "ERREUR AJOUT"; break;
		} 
	}
}

if(GETPOST('ResetAsset')){
	echo '<hr>REMISE A ZERO DES EQUIPEMENTS EN FONTION DES STOCK PRODUIT MATIERES PREMIERE<hr>';
	
	$sql = "SELECT p.rowid 
			FROM ".MAIN_DB_PREFIX."product as p
			WHERE p.finished = 0 AND stock > 0 AND fk_product_type = 0";

	$PDOdb->Execute($sql);
	$TProductIds = $PDOdb->Get_All();
	
	foreach ($TProductIds as $productid) {
		
		
		$sql = "SELECT rowid, contenance_value, contenancereel_value FROM ".MAIN_DB_PREFIX."asset WHERE fk_product = ".$productid->rowid." ORDER BY date_cre ASC";
		$PDOdb->Execute($sql);
		$TAssetIds = $PDOdb->Get_All();
		
		$product = new Product($db);
		$product->fetch($productid->rowid);
		$product->load_stock();
		
		echo $product->id." ".$product->libelle.'<br>';
		
		$cpt = 1;
		foreach ($TAssetIds as $row) {
			
			if(count($TAssetIds) != $cpt){
				$cpt++;
				$mouvementStock = new TAssetStock;
				$mouvementStock->mouvement_stock($PDOdb, $user, $row->rowid, ($row->contenance_value - $row->contenancereel_value), 'RAZ des stocks Equipements', 1);

				$PDOdb->Execute('UPDATE '.MAIN_DB_PREFIX.'asset SET contenancereel_value = 0 WHERE rowid = '.$row->rowid);
			}
			else{
				$cpt++;
				$mouvementStock = new TAssetStock;
				$mouvementStock->mouvement_stock($PDOdb, $user, $row->rowid, ($product->stock_reel - $row->contenancereel_value), 'RAZ des stocks Equipements', 1);
				
				$PDOdb->Execute('UPDATE '.MAIN_DB_PREFIX.'asset SET contenancereel_value = '.$product->stock_reel.' WHERE rowid = '.$row->rowid);
			}
		}
		
	}
}

if(GETPOST('UpdateAssetType')){
	$sql = "SELECT DISTINCT(a.rowid) FROM ".MAIN_DB_PREFIX.'asset as a';
	
	$PDOdb->Execute($sql);
	$TAssetIds = $PDOdb->Get_All();
	
	foreach ($TAssetIds as $row) {
		$asset = new TAsset;
		$asset->load($PDOdb, $row->rowid);
		$asset->load_asset_type($PDOdb);
		
		echo $asset->getId().' '.$asset->gestion_stock."  =>  ".$asset->assetType->gestion_stock.'<br>';
		
		$asset->gestion_stock = $asset->assetType->gestion_stock;
		$asset->contenance_value = $asset->assetType->contenance_value;
		$asset->save($PDOdb);
	}
}
