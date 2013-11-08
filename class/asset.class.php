<?php

class TAsset extends TObjetStd{
/*
 * Gestion des équipements 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_soc,fk_product,entity','type=entier;');
		$this->add_champs('commentaire', 'type=chaine;');
		
		//clé étrangère : type de la ressource
		parent::add_champs('fk_asset_type','type=entier;index;');
		
		$this->_init_vars('serial_number');
		
	    $this->start();
		
		$this->TLink=array(); // liaison document
		$this->TStock=array(); // liaison mouvement stock
		$this->error='Erreur dans objet equipement';
		
		$this->TField=array();
		$this->assetType=new TAsset_type;
		$this->TType = array();
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
		global $conf;
		
		$res = parent::load($db,$id);
		if($annexe)$this->load_link($db);
		$this->load_stock($db);
		$this->load_asset_type($db);
		//Sauvegarde de l'ancienne contenance réelle
		$this->old_contenancereel = $this->contenancereel_value;
		$this->old_contenancereel_units = $this->contenancereel_units;
		
		return $res;
	}
	
	function load_liste_type_asset(&$ATMdb){
		//chargement d'une liste de tout les types de ressources
		$temp = new TAsset_type;
		$Tab = TRequeteCore::get_id_from_what_you_want($ATMdb, MAIN_DB_PREFIX.'asset_type', array());
		$this->TType = array('');
		foreach($Tab as $k=>$id){
			$temp->load($ATMdb, $id);
			$this->TType[$temp->getId()] = $temp->libelle;
		}
		
	}
	
	function load_asset_type(&$ATMdb) {
		//on prend le type de ressource associé
		$Tab = TRequeteCore::get_id_from_what_you_want($ATMdb, MAIN_DB_PREFIX.'asset_type', array('rowid'=>$this->fk_asset_type));
		
		print_r($this->fk_asset_type);exit;
		
		$this->assetType->load($ATMdb, $Tab[0]);
		$this->fk_asset_type = $this->assetType->getId();
		
		//on charge les champs associés au type.
		$this->init_variables($ATMdb);
		
	}
	
	function init_variables(&$ATMdb) {
		foreach($this->assetType->TField as $field) {
			$this->add_champs($field->code, 'type=chaine;');
		}
		$this->init_db_by_vars($ATMdb);
		parent::load($ATMdb, $this->getId());
	}
	
	function save(&$db,$user='',$type = "Modification manuelle", $qty=0) {
		parent::save($db);
		$this->save_link($db);
		
		// Qty en paramètre est vide, on vérifie si le contenu du flacon a été modifié
		if(empty($qty) && $this->contenancereel_value * pow(10, $this->contenancereel_units) != $this->old_contenancereel * pow(10,$this->old_contenancereel_units)) {
			$qtyKg = $this->contenancereel_value * pow(10, $this->contenancereel_units) - $this->old_contenancereel * pow(10,$this->old_contenancereel_units);
			$qty = $qtyKg * pow(10, -$this->contenancereel_units);
		} else if(!empty($qty)) {
			$this->contenancereel_value = $this->contenancereel_value + $qty;
			parent::save($db);
		}
		
		// Enregistrement des mouvements
		if(!empty($qty))
		{
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

class TAsset_type extends TObjetStd {
	function __construct() { /* declaration */
		parent::set_table(MAIN_DB_PREFIX.'asset_type');
		parent::add_champs('libelle,code','type=chaine;');
		parent::add_champs('entity','type=entier;index;');
		parent::add_champs('supprimable','type=entier;');
				
		parent::_init_vars();
		parent::start();
		$this->TField=array();
		$this->TType=array('chaine'=>'Texte','entier'=>'Entier','float'=>'Float',"liste"=>'Liste','date'=>'Date', "checkbox"=>'Case à cocher');
	}
	
	
	function load_by_code(&$ATMdb, $code){
		$sqlReq="SELECT rowid FROM ".MAIN_DB_PREFIX."asset_type WHERE code='".$code."'";
		$ATMdb->Execute($sqlReq);
		
		if ($ATMdb->Get_line()) {
			$this->load($ATMdb, $ATMdb->Get_field('rowid'));
			return true;
		}
		return false;
	}
	
	/**
	 * Attribut les champs directement, pour créer les types par défauts par exemple. 
	 */
	function chargement(&$db, $libelle, $code, $supprimable){
		$this->load_by_code($db, $code);
		$this->libelle = $libelle;
		$this->code = $code;
		$this->supprimable = $supprimable;
		$this->save($db);
	}
	
	function load(&$ATMdb, $id) {
		parent::load($ATMdb, $id);
		$this->load_field($ATMdb);
	}
	
	/**
	 * Renvoie true si ce type est utilisé par une des ressources.
	 */
	function isUsedByRessource(&$ATMdb){
		$Tab = TRequeteCore::get_id_from_what_you_want($ATMdb, MAIN_DB_PREFIX.'asset', array('fk_asset_type'=>$this->getId()));
		if (count($Tab)>0) return true;
		return false;

	}
	
	function load_field(&$ATMdb) {
		global $conf;
		$sqlReq="SELECT rowid FROM ".MAIN_DB_PREFIX."asset_field WHERE fk_asset_type=".$this->getId()." ORDER BY ordre ASC;";
		$ATMdb->Execute($sqlReq);
		
		$Tab = array();
		while($ATMdb->Get_line()) {
			$Tab[]= $ATMdb->Get_field('rowid');
		}
		
		$this->TField=array();
		foreach($Tab as $k=>$id) {
			$this->TField[$k]=new TAsset_field;
			$this->TField[$k]->load($ATMdb, $id);
		}
	}
	
	function addField(&$ATMdb, $TNField) {
		$k=count($this->TField);
		$this->TField[$k]=new TAsset_field;
		$this->TField[$k]->set_values($TNField);
		
		$p=new TAsset;				
		$p->add_champs($TNField['code'] ,'type=chaine' );
		$p->init_db_by_vars($ATMdb);
					
		return $k;
	}
	
	function delField(&$ATMdb, $id){
		$toDel = new TAsset_field;
		$toDel->load($ATMdb,$id);
		return $toDel->delete($ATMdb);
	}
	
	function delete(&$ATMdb) {
		global $conf;
		if ($this->supprimable){
			//on supprime les champs associés à ce type
			$sqlReq="SELECT rowid FROM ".MAIN_DB_PREFIX."asset_field WHERE fk_asset_type=".$this->getId();
			$ATMdb->Execute($sqlReq);
			$Tab = array();
			while($ATMdb->Get_line()) {
				$Tab[]= $ATMdb->Get_field('rowid');
			}
			$temp = new TAsset_field;
			foreach ($Tab as $k => $id) {
				$temp->load($ATMdb, $id);
				$temp->delete($ATMdb);
			}
			//puis on supprime le type
			parent::delete($ATMdb);
			return true;
		}
		else {return false;}
		
	}
	function save(&$db) {
		global $conf;
		
		$this->entity = $conf->entity;
		$this->code = TAsset_type::code_format(empty($this->code) ? $this->libelle : $this->code);
		
		$this->code = TAsset_type::code_format(empty($this->code) ? $this->libelle : $this->code);
		
		parent::save($db);
		
		foreach($this->TField as $field) {
			$field->fk_asset_type = $this->getId();
			$field->save($db);
		}
		
	}	
	
	static function code_format($s){
		$r=""; $s = strtolower($s);
		$nb=strlen($s);
		for($i = 0; $i < $nb; $i++){
			if(ctype_alnum($s[$i])){
				$r.=$s[$i];			
			}
		} // for
		return $r;
	}
		
}

class TAsset_field extends TObjetStd {
	function __construct() { /* declaration */
		parent::set_table(MAIN_DB_PREFIX.'asset_field');
		parent::add_champs('code,libelle','type=chaine;');
		parent::add_champs('type','type=chaine;');
		parent::add_champs('obligatoire','type=entier;');
		parent::add_champs('ordre','type=entier;');
		parent::add_champs('options','type=chaine;');
		parent::add_champs('supprimable','type=entier;');
		parent::add_champs('inliste,inlibelle','type=chaine;'); //varchar booléen : oui/non si le champs sera dans la liste de Ressource.
		parent::add_champs('fk_asset_type,entity','type=entier;index;');
		
		$this->TListe = array();
		parent::_init_vars();
		parent::start();
		
	}
	
	function load_by_code(&$db, $code){
		$sqlReq="SELECT rowid FROM ".MAIN_DB_PREFIX."asset_field WHERE code='".$code."'";
		$db->Execute($sqlReq);
		
		if ($db->Get_line()) {
			$this->load($db, $db->Get_field('rowid'));
			return true;
		}
		return false;
	}
	
	
	function chargement(&$db, $libelle, $code, $type, $obligatoire, $ordre, $options, $supprimable, $fk_asset_type, $inliste = "non", $inlibelle = "non"){
		$this->load_by_code($db, $code);	
		$this->libelle = $libelle;
		$this->code = $code;
		$this->type = $type;
		$this->obligatoire = $obligatoire;
		$this->ordre = $ordre;
		$this->options = $options;
		$this->supprimable = $supprimable;
		$this->inliste = $inliste;
		$this->inlibelle = $inlibelle;
		$this->fk_asset_type = $fk_rh_ressource_type;
		
		
		$this->save($db);
	}
	
	function load(&$ATMdb, $id){
		parent::load($ATMdb, $id);
		$this->TListe = array();
		foreach (explode(";",$this->options) as $key => $value) {
			$this->TListe[$value] = $value;
		}
	}
	
	function save(&$db) {
		global $conf;
		
		$this->code = TAsset_type::code_format(empty($this->code) ? $this->libelle : $this->code);
		
		$this->entity = $conf->entity;
		if (empty($this->supprimable)){$this->supprimable = 0;}
		parent::save($db);
	}

	function delete(&$ATMdb) {
		global $conf;
		
		//on supprime le champs que si il est par défault.
		if (! $this->supprimable){
			parent::delete($ATMdb);	
			return true;
		}
		else {return false;}
		
		
	}

}

