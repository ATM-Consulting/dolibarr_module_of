<?php

class TAsset extends TObjetStd{
/*
 * Gestion des équipements 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_soc,fk_product,periodicity,qty,entity','type=entier;');
		
		$this->add_champs('copy_black,copy_color,contenancereel_value, contenance_value, tare', 'type=float;');
		$this->add_champs('contenance_units, contenancereel_units, tare_units', 'type=entier;');
		$this->add_champs('lot_number,emplacement,commentaire', 'type=chaine;');
		/*
		 * periodicity : nombre de jour depuis dernière intervention avant nouvelle intervention
		 * qty : quantité (champs présent dans la gestion oracle pour une raison qui nous échappe)
		 */
		
		$this->add_champs('date_achat,date_shipping,date_garantie,date_last_intervention','type=date;');
		
		$this->_init_vars('serial_number');
		
	    $this->start();
		
		$this->TLink=array(); // liaison document
		$this->TStock=array(); // liaison mouvement stock
		$this->error='Erreur dans objet equipement';
		
		$this->date_shipping=time();
		$this->date_achat=time();
		$this->date_garantie=time();
		$this->date_last_intervention=time();
		
		$this->old_contenancereel = 0;
	}

	function reinit() {
		$this->rowid = 0;
		$nb=count($this->TLink);
		for($i=0;$i<$nb;$i++) {
			$this->TLink[$i]->rowid=0;
			$this->TLink[$i]->fk_asset=0;
		}
		$nb=count($this->TStock);
		for($i=0;$i<$nb;$i++) {
			$this->TStock[$i]->rowid=0;
			$this->TStock[$i]->fk_asset=0;
		}
	}
	
	function load(&$db, $id, $annexe=true) {
		$res = parent::load($db,$id);
		if($annexe)$this->load_link($db);
		$this->load_stock($db);
		
		//Sauvegarde de l'ancienne contenance réelle
		$this->old_contenancereel = $this->contenancereel_value;
		$this->old_contenancereel_units = $this->contenancereel_units;
		
		return $res;
	}
	function save(&$db,$user='',$type = "Modification manuelle", $qty=0) {
		parent::save($db);
		$this->save_link($db);
		
		if($this->contenancereel_value * pow(10, $this->contenancereel_units) != $this->old_contenancereel * pow(10,$this->old_contenancereel_units))
		{
			$stock = new TAssetStock;
			$stock->mouvement_stock($db, $user, $this->rowid, $this->contenancereel_value - $this->old_contenancereel, $type, $this->rowid);
			
			// Mouvement de stock standard Dolibarr, attention Entrepôt 1 mis en dur
			global $db, $user;
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$mouvS = new MouvementStock($db);
			// We decrement stock of product (and sub-products)
			// We use warehouse selected for each line
			if($this->contenancereel_value - $this->old_contenancereel < 0) {
				$result=$mouvS->reception($user, $this->fk_product, 1, $this->contenancereel_value - $this->old_contenancereel, 0, $type);
			} else {
				$result=$mouvS->livraison($user, $this->fk_product, 1, $this->contenancereel_value - $this->old_contenancereel, 0, $type);
			}
		}
		elseif($qty != 0){
			$this->contenancereel_value = $this->contenancereel_value + $qty;
			parent::save($db);
			
			$stock = new TAssetStock;
			$stock->mouvement_stock($db, $user, $this->rowid, $qty, $type, $this->rowid);
			
			// Mouvement de stock standard Dolibarr, attention Entrepôt 1 mis en dur
			global $db, $user;
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

			$mouvS = new MouvementStock($db);
			// We decrement stock of product (and sub-products)
			// We use warehouse selected for each line
			if($qty > 0) {
				$result=$mouvS->reception($user, $this->fk_product, 1, $qty, 0, $type);
			} else {
				$result=$mouvS->livraison($user, $this->fk_product, 1, -$qty, 0, $type);
			}
		}
	}
	function delete(&$db) {
		parent::delete($db);
		$nb=count($this->TLink);
		for($i=0;$i<$nb;$i++) {
			$this->TLink[$i]->delete($db);	
		}
		$nb=count($this->TStock);
		for($i=0;$i<$nb;$i++) {
			$this->TStock[$i]->delete($db);	
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
	
	function load_stock($db){
		$this->TStock=array();
		$Tab = $this->_get_stock_id($db);
		
		foreach ($Tab as $i=>$id) {
			$this->TStock[$i]=new TAssetStock;
			$this->TStock[$i]->load($db, $id);	
		}
	}
	
	private function _get_stock_id(&$db) {
		$db->Execute("SELECT rowid FROM ".$this->get_table()."_stock WHERE fk_asset=".$this->rowid." ORDER BY date_cre DESC");
		$Tab=array();
		while($db->Get_line()) {
			$Tab[]=$db->Get_field('rowid');
		}
		
		return $Tab;
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


class TAssetCommandedet extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de commande et les lots 
 */	
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'commandedet');	  
		parent::add_champs('asset_lot','type=chaine;');
				
		parent::_init_vars();
		
	    parent::start();
	}
}

class TAssetFacturedet extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de facture et les lots 
 */
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'facturedet');	  
		parent::add_champs('asset_lot','type=chaine;');
				
		parent::_init_vars();
		
	    parent::start();
	}
}

class TAssetStock extends TObjetStd{
/*
 * Gestion des mouvements de stock pour les équipements
 */
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'asset_stock');
		parent::add_champs('fk_asset','type=eniter;index;');	  
		parent::add_champs('qty','type=float;');
		parent::add_champs('date_mvt','type=date;');
		parent::add_champs('type,lot','type=chaine;');
		parent::add_champs('source,user,weight_units','type=entier;');
				
		parent::_init_vars();
		
	    parent::start();
	}
	
	//Création d'une nouvelle entrée en stock
	function mouvement_stock(&$ATMdb,$user,$fk_asset,$qty,$type,$id_source){
		
		$asset = new TAsset;
		$asset->load($ATMdb, $fk_asset);
		
		$this->fk_asset = $fk_asset;
		$this->qty = $qty;
		$this->date_mvt = date('Y-m-d H:i:s');
		$this->type = $type;
		$this->source = $id_source;
		$this->lot = $asset->lot_number;
		$this->user = $user->id;
		$this->weight_units = $asset->contenancereel_units;
		
		$this->save($ATMdb);
	}
	
	//Récupère la quantité de la dernière entrée en stock
	function get_last_mouvement(&$ATMdb,$fk_asset){
		$sql = "SELECT qty FROM ".MAIN_DB_PREFIX."asset_stock WHERE fk_asset = ".$fk_asset." ORDER BY rowid DESC LIMIT 1";
		$ATMdb->Execute($sql);
		if($ATMdb->Get_line())
			return $ATMdb->Get_field("qty");
		else 
			return "error";
	}
}

?>
