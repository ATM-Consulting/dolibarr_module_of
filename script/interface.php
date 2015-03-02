<?php

define('INC_FROM_CRON_SCRIPT', true);
set_time_limit(0);
require('../config.php');
require('../lib/asset.lib.php');
require('../class/asset.class.php');
require('../class/ordre_fabrication_asset.class.php');

//Interface qui renvoie les emprunts de ressources d'un utilisateur
$ATMdb=new TPDOdb;

$get = __get('get','emprunt');

traite_get($ATMdb, $get);

function traite_get(&$ATMdb, $case) {
	switch (strtolower($case)) {
		case 'autocomplete':
			__out(_autocomplete($ATMdb,$_REQUEST['fieldcode'],$_REQUEST['term'],$_REQUEST['fk_product']));
			break;
		case 'addofproduct':
			__out(_addofproduct($ATMdb,$_REQUEST['id_assetOf'],$_REQUEST['fk_product'],$_REQUEST['type']));
			break;
		case 'deletelineof':
			__out(_deletelineof($ATMdb,$_REQUEST['idLine'],$_REQUEST['type']));
			break;
		case 'addlines':
			__out(_addlines($ATMdb,$_REQUEST['idLine'],$_REQUEST['qty']),$_REQUEST['type']);
			break;
		case 'addofworkstation':
			__out(_addofworkstation($ATMdb,$_REQUEST['id_assetOf'],$_REQUEST['fk_asset_workstation']));
			break;	
		case 'deleteofworkstation':	
			__out(_deleteofworkstation($ATMdb,$_REQUEST['id_assetOf'], $_REQUEST['fk_asset_workstation_of'] ));
			break;
		case 'measuringunits':
			__out(_measuringUnits(GETPOST('type'), GETPOST('name')), 'json');
			break;
		case 'getofchildid':
			$Tid = array();
			$assetOf=new TAssetOF;
			$assetOf->load($ATMdb, __get('id',0,'integer'));
			
			$assetOf->getListeOFEnfants($ATMdb, $Tid);
			
			__out($Tid);
			break;
	}
}

function _addofworkstation(&$ATMdb, $id_assetOf, $fk_asset_workstation, $nb_hour=0) {
	
	$of=new TAssetOF;
	$of->load($ATMdb, $id_assetOf);
	
	$k = $of->addChild($ATMdb, 'TAssetWorkstationOF');
	
	$of->TAssetWorkstationOF[$k]->fk_asset_workstation = $fk_asset_workstation;
	$of->TAssetWorkstationOF[$k]->nb_hour = $nb_hour;
	$of->save($ATMdb);
	
	
}
function _deleteofworkstation(&$ATMdb, $id_assetOf, $fk_asset_workstation_of) 
{
	$of=new TAssetOF;
	$of->load($ATMdb, $id_assetOf);
	$of->removeChild('TAssetWorkstationOF', $fk_asset_workstation_of);
	$of->save($ATMdb);	
}

//Autocomplete sur les différents champs d'une ressource
function _autocomplete(&$ATMdb,$fieldcode,$value,$fk_product=0)
{
	$value = trim($value);
	
	$sql = 'SELECT DISTINCT(al.'.$fieldcode.') ';
	$sql .= 'FROM '.MAIN_DB_PREFIX.'assetlot as al ';
	
	if($fk_product)
	{
		$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'asset as a ON (a.'.$fieldcode.' = al.'.$fieldcode.') ';
		$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = a.fk_product) ';
	}
	
	if (!empty($value)) $sql .= 'WHERE '.$fieldcode.' LIKE '.$ATMdb->quote($value.'%').' ';
	
	if (!empty($value) && $fk_product) $sql .= 'AND p.rowid = '.(int) $fk_product.' ';
	elseif ($fk_product) $sql .= 'WHERE p.rowid = '.(int) $fk_product.' ';
	
	$sql .= 'ORDER BY al.'.$fieldcode;
		
	$ATMdb->Execute($sql);
	while ($ATMdb->Get_line()) 
	{
		$TResult[] = $ATMdb->Get_field($fieldcode);
	}
	
	$ATMdb->close();
	return $TResult;
}

function _addofproduct(&$ATMdb,$id_assetOf,$fk_product,$type,$qty=1, $lot_number = ''){
	
	global $db;
	
	$TassetOF = new TAssetOF;
	$TassetOF->load($ATMdb, $id_assetOf);
	$TassetOF->addLine($ATMdb, $fk_product, $type,$qty,0, $lot_number);
	$TassetOF->save($ATMdb);
	
	// Pour ajouter directement les stations de travail, attachées au produit grâce à l'onglet "station de travail" disponible dans la fiche produit
	if($type == "TO_MAKE") {
		$sql = "SELECT fk_asset_workstation, nb_hour";
		$sql.= " FROM ".MAIN_DB_PREFIX."asset_workstation_product";
		$sql.= " WHERE fk_product = ".$fk_product;
		$resql = $db->query($sql);
		
		if($resql) {
			
			while($res = $db->fetch_object($resql)) {
				
				_addofworkstation($ATMdb, $id_assetOf, $res->fk_asset_workstation, $res->nb_hour);

			}
			
		}
		
	}

}

function _deletelineof(&$ATMdb,$idLine,$type){
	$TAssetOFLine = new TAssetOFLine;
	$TAssetOFLine->load($ATMdb, $idLine);	
	$TAssetOFLine->delete($ATMdb);
}

function _addlines(&$ATMdb,$idLine,$qty){
	global $db, $conf;
	
	dol_include_once('product/class/product.class.php');
	
	//On met à jour la 1ère ligne des TO_MAKE
	$TAssetOFLine = new TAssetOFLine;
	//$ATMdb->debug = true;
	$TAssetOFLine->load($ATMdb, $idLine);
	$TAssetOFLine->qty = $_REQUEST['qty'];
	$TAssetOFLine->save($ATMdb);

	//On charge l'OF pour pouvoir parcourir ses lignes et mettre à jour les quantités
	$TAssetOF = new TAssetOF;
	$TAssetOF->load($ATMdb, $TAssetOFLine->fk_assetOf);
	
	$TIdLineModified = array($TAssetOFLine->fk_assetOf);
	
 	_updateNeeded($TAssetOF, $ATMdb, $db, $conf, $TAssetOFLine->fk_product, $_REQUEST['qty'], $TIdLineModified);
	
	return $TIdLineModified;
}

function _updateToMake($TAssetOFChildId = array(), &$ATMdb, &$db, &$conf, $fk_product, $qty, &$TIdLineModified)
{
	$break = false;
	foreach ($TAssetOFChildId as $idOF)
	{
		$TAssetOF = new TAssetOF;
		$TAssetOF->load($ATMdb, $idOF);
		
		foreach ($TAssetOF->TAssetOFLine as $line) 
		{
			//Si le produit TO_MAKE de cette OF correspond au notre, on maj sa qté ainsi que ces needed et on stop le traitement pcq pas besoin d'aller plus loin
			if ($line->type == 'TO_MAKE' && $line->fk_product == $fk_product)
			{
				$TIdLineModified[] = $TAssetOF->rowid;
				$line->qty = $qty;
				$line->save($ATMdb);
				
				_updateNeeded($TAssetOF, $ATMdb, $db, $conf, $line->fk_product, $line->qty, $TIdLineModified, true);
				$break = true;
				break;
			}
		}
		
		if ($break) break;
	}
}

function _measuringUnits($type, $name)
{
	global $db;
	
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	
	$html=new FormProduct($db);
	
	if($type == 'unit') return array(' unité(s)');
	else return array($html->load_measuring_units($name, $type, 0));
}

function _updateNeeded($TAssetOF, &$ATMdb, &$db, &$conf, $fk_product, $qty, &$TIdLineModified)
{
	$prod = new Product($db);
	$prod->fetch($fk_product);
	$TComposition = $prod->getChildsArbo($prod->id);
	
	if (empty($TComposition)) return;
	
	$TAssetOFChildId = array();
	$TAssetOF->getListeOFEnfants($ATMdb, $TAssetOFChildId, $TAssetOF->rowid, false);
	
	foreach ($TAssetOF->TAssetOFLine as $line) 
	{
		// On ne modifie les quantités que des produits NEEDED qui sont des sous produits du produit TO_MAKE
		if($line->type == 'NEEDED' && !empty($TComposition[$line->fk_product][1])) 
		{
			$line->qty = $line->qty_needed = $line->qty_used = $qty * $TComposition[$line->fk_product][1];
			$line->save($ATMdb);		

			if (!empty($TAssetOFChildId)) _updateToMake($TAssetOFChildId, $ATMdb, $db, $conf, $line->fk_product, $line->qty, $TIdLineModified);		
		}
	}	
}