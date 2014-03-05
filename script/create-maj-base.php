<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 * 
 */
 	define('INC_FROM_CRON_SCRIPT', true);
	
	require('../config.php');
	require('../class/asset.class.php');
	require('../class/ordre_fabrication_asset.class.php');

	$ATMdb=new TPDOdb;
	$ATMdb->debug=true;
	
	$o=new TAsset_type;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAsset_field;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAsset;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetCommandedet;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetFacturedet;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetStock;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetOF;
	$o->init_db_by_vars($ATMdb);

	$o=new TAssetOFLine;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetWorkstation;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetWorkstationOF;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetWorkstationProduct;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetPropaldet;
	$o->init_db_by_vars($ATMdb);
	
	$ATMdb->Execute("REPLACE INTO `llx_extrafields` (`rowid`, `name`, `entity`, `elementtype`, `tms`, `label`, `type`, `size`, `fieldunique`, `fieldrequired`, `pos`, `param`) VALUES
(40, 'type_asset', 1, 'product', '2013-12-04 13:27:49', 'Type Equipement', 'sellist', '', 0, 0, 1, 'a:1:{s:7:\"options\";a:1:{s:24:\"asset_type:libelle:rowid\";N;}}');");
