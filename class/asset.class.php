<?php

class TAsset extends TObjetStd{
/*
 * Gestion des équipements 
 * */
	
	function __construct() {
		$this->set_table('llx_asset');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_soc,fk_product,fk_affaire,periodicity,qty,entity','type=entier;');
		
		$this->add_champs('copy_black,copy_color', 'type=float;');
		
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
	function load(&$db, $id) {
		parent::load($db,$id);
		$this->load_link($db);
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
	
	private function _get_link_id(&$db) {
		$db->Execute("SELECT rowid FROM ".$this->get_table()."_link WHERE fk_asset=".$this->rowid);
		$Tab=array();
		while($db->Get_line()) {
			$Tab[]=$db->Get_field('rowid');
		}
		
		return $Tab;
	}
	
	function add_link($fk_document, $type_document) {
		$i=count($this->TLink);
		$this->TLink[$i]=new TAssetLink;
		$this->TLink[$i]->fk_asset=$this->rowid;
		$this->TLink[$i]->fk_document=$fk_document;
		$this->TLink[$i]->type_document=$type_document;	
		
		return $i;
	}
	
} 
class TAssetLink extends TObjetStd{
/*
 * Liaison entre les équipements et les documents
 */	
	function __construct() {
		$this->set_table('llx_asset_link');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_asset,fk_document','type=entier;');
				
		$this->_init_vars('type_document');
		
	    $this->start();
	}
	
}

?>
