<?php

define('INC_FROM_CRON_SCRIPT', true);
set_time_limit(0);
require('../config.php');
require('../lib/asset.lib.php');
require('../class/asset.class.php');
require('../class/ordre_fabrication_asset.class.php');

//Interface qui renvoie les emprunts de ressources d'un utilisateur
$ATMdb=new TPDOdb;

$get = isset($_REQUEST['get'])?$_REQUEST['get']:'emprunt';

_get($ATMdb, $get);

function _get(&$ATMdb, $case) {
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
	$TAssetOFLine->load($ATMdb, $idLine);
	$TAssetOFLine->save($ATMdb);
	
	__deleteOldLines($ATMdb,$idLine);
	
	_addofproduct($ATMdb, $TAssetOFLine->fk_assetOf, $TAssetOFLine->fk_product, "TO_MAKE",$qty);
}

function __deleteOldLines(&$ATMdb,$idLine){
	$TId = TRequeteCore::get_id_from_what_you_want($ATMdb, MAIN_DB_PREFIX.'assetOf_line','fk_assetOf_line_parent = '.$idLine.' OR rowid = '.$idLine);
	
	foreach($TId as $cle => $id){
		$assetofline = new TAssetOFLine;
		$assetofline->load($ATMdb, $id);
		$assetofline->delete($ATMdb);
	}
}
