<?php

class TAsset extends TObjetStd{
/*
 * Gestion des équipements 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_soc,fk_product,periodicity,qty,entity','type=entier;');
		
		$this->add_champs('copy_black,copy_color,', 'type=float;');
		$this->add_champs('contenance_value,contenance_units', 'type=entier;');
		
		/*
		 * periodicity : nombre de jour depuis dernière intervention avant nouvelle intervention
		 * qty : quantité (champs présent dans la gestion oracle pour une raison qui nous échappe)
		 */
		
		$this->add_champs('date_achat,date_shipping,date_garantie,date_last_intervention','type=date;');
		
		$this->_init_vars('serial_number');
		
	    $this->start();
		
		$this->TLink=array(); // liaison document
		$this->error='Erreur dans objet equipement';
		
		$this->date_shipping=time();
		$this->date_achat=time();
		$this->date_garantie=time();
		$this->date_last_intervention=time();
	}
	function reinit() {
		$this->rowid = 0;
		$nb=count($this->TLink);
		for($i=0;$i<$nb;$i++) {
			$this->TLink[$i]->rowid=0;
			$this->TLink[$i]->fk_asset=0;
		}
	}
	
	function load(&$db, $id, $annexe=true) {
		$res = parent::load($db,$id);
		if($annexe)$this->load_link($db);
		
		return $res;
	}
	function save(&$db) {
		parent::save($db);
		
		$this->save_link($db);
	}
	function delete(&$db) {
		parent::delete($db);
		$nb=count($this->TLink);
		for($i=0;$i<$nb;$i++) {
			$this->TLink[$i]->delete($db);	
		}
	}
	function load_link(&$db) {
		$this->TLink=array();
		$Tab = $this->_get_link_id($db);
		
		foreach ($Tab as $i=>$id) {
			$this->TLink[$i]=new TAssetLink;
			$this->TLink[$i]->load($db, $id);	
		}
	}
	function save_link(&$db) {
		$nb=count($this->TLink);
		for($i=0;$i<$nb;$i++) {
			$this->TLink[$i]->fk_asset=$this->rowid;
			$this->TLink[$i]->save($db, $id);	
		}
	}
	function getLink($type_document='') {
		
		foreach($this->TLink as &$link) {
			if($link->type_document==$type_document) {
				return $link;
			}
		}
		
	}
	private function _get_link_id(&$db) {
		$db->Execute("SELECT rowid FROM ".$this->get_table()."_link WHERE fk_asset=".$this->rowid);
		$Tab=array();
		while($db->Get_line()) {
			$Tab[]=$db->Get_field('rowid');
		}
		
		return $Tab;
	}
	
	function add_link($fk_document, $type_document) {
		foreach($this->TLink as &$link) {
			if($link->fk_document==$fk_document && $link->type_document==$type_document) return false;
		}	
			
		$i=count($this->TLink);
		$this->TLink[$i]=new TAssetLink;
		$this->TLink[$i]->fk_asset=$this->rowid;
		$this->TLink[$i]->fk_document=$fk_document;
		$this->TLink[$i]->type_document=$type_document;	
		
		return $i;
	}
	
	function loadReference(&$db, $serial_number) {
		
		$db->Execute("SELECT rowid FROM ".$this->get_table()." WHERE serial_number='".$serial_number."'");
		if($db->Get_line()) {
			return $this->load($db, $db->Get_field('rowid'));
		}
		else {
			return false;
		}
		
	}
	
	
} 
class TAssetLink extends TObjetStd{
/*
 * Liaison entre les équipements et les documents
 */	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_link');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_asset,fk_document','type=entier;');
				
		$this->_init_vars('type_document');
		
	    $this->start();
	    
		$this->asset = new TAsset;
	}
	function load(&$db, $id, $annexe=false) {
		parent::load($db, $id);
		
		if($annexe){
			$this->asset->load($db, $this->fk_asset, false);
		}
	}
	
}

?>
