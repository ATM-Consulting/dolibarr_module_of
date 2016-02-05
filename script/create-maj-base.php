<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 * 
 */
    if(!defined('INC_FROM_DOLIBARR')) {
        define('INC_FROM_CRON_SCRIPT', true);
        require('../config.php');
        $ATMdb=new TPDOdb;
        $ATMdb->debug=true;
    }
    else{
        $ATMdb=new TPDOdb;
        
    }
	
	global $db;
	
	dol_include_once('/of/class/ordre_fabrication_asset.class.php');

    $o=new TAssetOF;
	$o->init_db_by_vars($ATMdb);

	$o=new TAssetOFLine;
	$o->init_db_by_vars($ATMdb);
	if (class_exists('TWorkstation')) {
		$o=new TAssetWorkstation;
		$o->init_db_by_vars($ATMdb);
			
	}
	else {
		exit($langs->trans("moduleWorkstationNeeded").' : <a href="https://github.com/ATM-Consulting/dolibarr_module_workstation" target="_blank">'.$langs->trans('DownloadModule').'</a>');
	}
	
	$o=new TAssetWorkstationOF;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetWorkstationProduct;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetControl;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetControlMultiple;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetOFControl;
	$o->init_db_by_vars($ATMdb);
	
	$o=new TAssetWorkstationTask;
	$o->init_db_by_vars($ATMdb);
	
	
	