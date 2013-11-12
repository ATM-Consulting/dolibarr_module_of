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
		
		//clé étrangère : atelier
		parent::add_champs('fk_asset_workstation','type=entier;index;');
		
		parent::add_champs('fk_assetOf_parent','type=entier;index;');
		
	    $this->start();
		
		$this->TType=array('NEEDED','TO_MAKE');
		$this->TOrdre=array(1=>'Au plut tôt',2=>'Dans la journée',3=>'Demain',4=>'Dans la semaine',5=>'Dans le mois');
		$this->TStatus=array('Brouillon','Lancé','Terminé');
		$this->TWorkstation=array();
		
		//Tableaux de produit lié à l'OF
		$this->TNeededProduct=array();
		$this->TToMakeProduct=array();
	}
	
	function load(&$db, $id) {
		global $conf;
		
		$res = parent::load($db,$id);
		$this->loadWorkstations($db);
		
		return $res;
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
	
	function createCommandeFournisseur(){
		
		return $id_cmd_four;
	}
	
	function loadWorkstations(&$ATMdb){
		$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."asset_workstation";
		$ATMdb->Execute($sql);
		while($ATMdb->Get_line()){
			$this->TWorkstation[$ATMdb->Get_field('rowid')]=$ATMdb->Get_field('libelle');
		}
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
