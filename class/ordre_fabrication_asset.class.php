<?php

class TAssetOF extends TObjetStd{
/*
 * Ordre de fabrication d'équipement
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf');
    	$this->TChamps = array(); 	  
		$this->add_champs('numero,entity,fk_user','type=entier;');
		$this->add_champs('entity','type=float;');
		$this->add_champs('ordre','type=chaine;');
		$this->add_champs('date_besoin,date_lancement,temps_estime_fabrication,temps_reel_fabrication','type=date;');
		
		//clé étrangère : type de la ressource
		parent::add_champs('fk_asset_workstation','type=entier;index;');
		
	    $this->start();
		
		$this->TType=array('NEEDED','TO_MAKE');
		$this->TOrdre=array('Au plut tôt','Dans la journée','Demain','Dans la semaine','Dans le mois');
		
		//Tableaux de produit lié à l'OF
		$this->TNeededProduct=array();
		$this->TToMakeProduct=array();
	}
	
	function setEquipement(){
		
		return true;
	}
	
	function delLine(&$ATMdb,$id_line){
		
		return true;
	}
	
	function addLine(&$ATMdb,$id_product,$type){
		
		return $id_line;
	}
	
	function addProductComposition($id_product){
		
		return true;
	}
	
	function getProductComposition($id_product){
		
		return array();
	}
}

class TAssetOF_line extends TObjetStd{
/*
 * Ligne d'Ordre de fabrication d'équipement 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf_line');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_assetOf,fk_product','type=entier;');
		$this->add_champs('type','type=chaine;');
		
		//clé étrangère : type de la ressource
		parent::add_champs('fk_assetOf_line_parent','type=entier;index;');
		
	    $this->start();
	}
	
	function setAsset(){
		
		return true;
	}
	
	function makeAsset(){
		
		return true;
	}
}

class TAssetWorkstation extends TObjetStd{
/*
 * Atelier de fabrication d'équipement
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity','type=entier;');
		$this->add_champs('libelle','type=chaine;');
		
	    $this->start();
	}
}
