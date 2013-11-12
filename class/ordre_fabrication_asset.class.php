<?php

class TAssetOF extends TObjetStd{
/*
 * Ordre de fabrication d'équipement
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf');
    	$this->TChamps = array(); 	  
		$this->add_champs('numero,entity,fk_user','type=entier;');
		$this->add_champs('entity,temps_estime_fabrication,temps_reel_fabrication','type=float;');
		$this->add_champs('ordre','type=chaine;');
		$this->add_champs('date_besoin,date_lancement','type=date;');
		
		//clé étrangère : atelier
		parent::add_champs('fk_asset_workstation','type=entier;index;');
		
		parent::add_champs('fk_assetOf_parent','type=entier;index;');
		
	    $this->start();
		
		$this->TOrdre=array('Au plut tôt','Dans la journée','Demain','Dans la semaine','Dans le mois');
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
	
	//Associe les équipements à l'OF
	function setEquipement($TAssetNeeded,$TAssetToMake){
		
		//Affectation des équipement pour les produit nécessaire
		foreach($this->TNeededProduct as $TNeededProduct){
			$TNeededProduct->setAsset($TAssetNeeded[$TNeededProduct->rowid]);
		}
		
		//Affectation des équipement pour les produit à créer
		foreach($this->TToMakeProduct as $ToMakeProduct){
			$ToMakeProduct->setAsset($TAssetToMake[$ToMakeProduct->rowid]);
		}
		
		return true;
	}
	
	function delLine(&$ATMdb,$id_line){
		$TAssetOF_line = new TAssetOF_line;
		if($TAssetOF_line->load($ATMdb, $id_line)){
			$TAssetOF_line->delete($ATMdb);
			return true;	
		}
		else
			return false;
	}
	
	//Ajout d'un produit TO_MAKE à l'OF
	function addProductComposition($id_product){
		global $user;
		
		$PDOdb = new TPDOdb;
		
		$TAssetOF_line = new TAssetOF_line;
		$TAssetOF_line->entity = $user->entity;
		$TAssetOF_line->fk_assetOf = $this->rowid;
		$TAssetOF_line->fk_product = $id_product;
		$TAssetOF_line->type = 'TO_MAKE';
		$TAssetOF_line->save($PDOdb);
		
		$PDOdb->close();
		
		return true;
	}
	
	//Retourne les produits NEEDED de l'OF concernant le produit $id_produit
	function getProductComposition($id_product){
		$Tab = array();
		
		$PDOdb = new TPDOdb;
		
		$Tid = TRequeteCore::get_id_from_what_you_want($PDOdb, MAIN_DB_PREFIX.'assetOf_line', array('fk_product'=>$id_product));
		
		foreach($Tid as $id){
			$TAssetOF_line = new TAssetOF_line;
			$Tab[] = $TAssetOF_line->load($PDOdb, $id);
		}
		
		$PDOdb->close();
		
		return $Tab;
	}
	
	function createCommandeFournisseur($type='externe'){
		
		
		
		return $id_cmd_four;
	}
	
	function loadWorkstations(&$ATMdb){
		$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."asset_workstation";
		$ATMdb->Execute($sql);
		while($ATMdb->Get_line()){
			$this->TWorkstation[$ATMdb->Get_field('rowid')]=$ATMdb->Get_field('libelle');
		}
	}
	
	//charge le produit TO_MAKE pour l'OF et ajoute une ligne correspondante s'il n'existe pas
	function loadToMakeProduct(&$ATMdb,$fk_product){
		global $db, $user;
		
		$TAssetOF_line = new TAssetOF_line;
		if($TAssetOF_line->load($fk_product)){
			$this->TToMakeProduct[] = $TAssetOF_line;
		}
		else {
			$this->addLine($fk_product,'TO_MAKE');		
		}
	}
	
	//Ajoute une ligne de produit à l'OF
	function addLine($fk_product,$type){
		if($type=='TO_MAKE')
			$this->addProductComposition($fk_product,$type);
		else
			$this->getProductComposition($fk_product);
		
	}
}

class TAssetOF_line extends TObjetStd{
/*
 * Ligne d'Ordre de fabrication d'équipement 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf_line');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_assetOf,fk_product,fk_asset','type=entier;');
		$this->add_champs('type','type=chaine;');
		
		//clé étrangère
		parent::add_champs('fk_assetOf_line_parent','type=entier;index;');
		
		$this->TType=array('NEEDED','TO_MAKE');
		
	    $this->start();
	}
	
	//Affecte l'équipement à la ligne de l'OF
	function setAsset($idAsset){
		
		$asset = new TAsset;
		$asset->load($ATMdb, $idAsset);
		$asset->status = 'indisponible';
		$asset->save($ATMdb);
		
		$this->fk_asset = $idAsset;
		$this->save($ATMdb);
		
		return true;
	}
	
	//Utilise l'équipement affecté à la ligne de l'OF
	function makeAsset(){
		
		$TAsset = new TAsset;
		$TAsset->load($ATMdb, $this->fk_asset);
		$TAsset->destock();
		
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
