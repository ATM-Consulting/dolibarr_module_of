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
	
	$o=new TAssetPropal;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetCommande;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetFacture;
	$o->init_db_by_vars($ATMdb);
	
	$asset_type = new TAsset_type;
	$asset_type->libelle = "Produit";
	$asset_type->reutilisable = "oui";
	$asset_type->code = "pr";
	$asset_type->gestion_stock = "UNIT";
	$asset_type->entity = $conf->entity;
	$asset_type->contenance_value = 0;
	$asset_type->contenancereel_value = 0;
	$asset_type->point_chute = 1;
	$asset_type->contenance_units = 0;
	$asset_type->contenancereel_units = 0;
	$asset_type->supprimable = 0;
	
	$asset_type->save($ATMdb);
	