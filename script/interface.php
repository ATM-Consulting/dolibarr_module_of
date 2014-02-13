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
			__out(_autocomplete($ATMdb,$_REQUEST['fieldcode'],$_REQUEST['term']));
			break;
		case 'addofproduct':
			__out(_addofproduct($ATMdb,$_REQUEST['id_assetOf'],$_REQUEST['fk_product'],$_REQUEST['type']));
			break;
		case 'deletelineof':
			__out(_deletelineof($ATMdb,$_REQUEST['idLine'],$_REQUEST['type']));
			break;
		case 'addlines':
			__out(_addlines($ATMdb,$_REQUEST['idLine'],$_REQUEST['qty']));
			break;
		case 'addofworkstation':
			__out(_addofworkstation($ATMdb,$_REQUEST['id_assetOf'],$_REQUEST['fk_asset_workstation']));
			break;	
		case 'deleteofworkstation':	
			
			__out(_deleteofworkstation($ATMdb,$_REQUEST['id_assetOf'], $_REQUEST['fk_asset_workstation_of'] ));
			
			break;
			
	}
}

function _addofworkstation(&$ATMdb, $id_assetOf, $fk_asset_workstation) {
	
	$of=new TAssetOF;
	$of->load($ATMdb, $id_assetOf);
	
	$k = $of->addChild($ATMdb, 'TAssetWorkstationOF');
	
	$of->TAssetWorkstationOF[$k]->fk_asset_workstation = $fk_asset_workstation;
	$of->save($ATMdb);
	
	
}
function _deleteofworkstation(&$ATMdb, $id_assetOf, $fk_asset_workstation_of) {
	
	$of=new TAssetOF;
	$of->load($ATMdb, $id_assetOf);
	
	$of->removeChild('TAssetWorkstationOF', $fk_asset_workstation_of);
	
	$of->save($ATMdb);
	
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

function _addofproduct(&$ATMdb,$id_assetOf,$fk_product,$type,$qty=1){
	$TassetOF = new TAssetOF;
	$TassetOF->load($ATMdb, $id_assetOf);
	$TassetOF->addLine($ATMdb, $fk_product, $type,$qty);
	$TassetOF->save($ATMdb);
}

function _deletelineof(&$ATMdb,$idLine,$type){
	$TAssetOFLine = new TAssetOFLine;
	$TAssetOFLine->load($ATMdb, $idLine,$qty,$fkassetOfLineParent);
	$TAssetOFLine->delete($ATMdb);
}

function _addlines(&$ATMdb,$idLine,$qty){

	$TAssetOFLine = new TAssetOFLine;
	//$ATMdb->debug = true;
	$TAssetOFLine->load($ATMdb, $idLine);
	
	$TAssetOFLine->delete($ATMdb);
	
	_addofproduct($ATMdb, $TAssetOFLine->fk_assetOf, $TAssetOFLine->fk_product, "TO_MAKE",$qty);
}
