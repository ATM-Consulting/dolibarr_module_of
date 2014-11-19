<?php

class TAsset extends TObjetStd{
/*
 * Gestion des équipements 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_soc,fk_product,fk_societe_localisation,entity','type=entier;');
		$this->add_champs('contenancereel_value, contenance_value,point_chute', 'type=float;');
		$this->add_champs('contenance_units, contenancereel_units, fk_entrepot', 'type=entier;');
		$this->add_champs('commentaire,lot_number,gestion_stock,reutilisable,status', 'type=chaine;');
		
		//clé étrangère : type de la ressource
		parent::add_champs('fk_asset_type','type=entier;index;');
		
		$this->_init_vars('serial_number');
		
	    $this->start();
		
		$this->TLink=array(); // liaison document
		$this->TStock=array(); // liaison mouvement stock
		$this->error='Erreur dans objet equipement';
		
		$this->TGestionStock = array(
				'UNIT'=>'Unitaire',
				'QUANTITY'=>'Quantitative'
			);
		
		$this->TStatus = array(
				'NOTUSED'=>'Non consommé',
				'PARTUSED'=>'Partiellement consommé',
				'USED'=>'Consommé'
			);
		
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
	
	function get_asset_type(&$ATMdb,$fk_product){
		
		$sql = "SELECT type_asset FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$fk_product;
		
		$ATMdb->Execute($sql);
		$ATMdb->Get_line();
		
		return $ATMdb->Get_field('type_asset');
	}
	
	function load_asset_type(&$ATMdb) {
		//on prend le type de ressource associé
		$Tab = TRequeteCore::get_id_from_what_you_want($ATMdb, MAIN_DB_PREFIX.'asset_type', array('rowid'=>$this->fk_asset_type));

		$this->assetType->load($ATMdb, $Tab[0]);
		$this->fk_asset_type = $this->assetType->getId();
		
		//on charge les champs associés au type.
		$this->init_variables($ATMdb);

	}
	
	function init_variables(&$ATMdb) {
		foreach($this->assetType->TField as $field) {
			$this->add_champs($field->code, 'type='.$field->type.';');
		}
		//$this->_init_vars();
		$this->init_db_by_vars($ATMdb); //TODO c'est a chier
		parent::load($ATMdb, $this->getId());
	}
	
	function save(&$ATMdb,$user='',$description = "Modification manuelle", $qty=0, $destock_dolibarr_only = false, $fk_prod_to_destock=0, $no_destock_dolibarr = false) {
		
		global $conf;
				
		if(!$destock_dolibarr_only) {
			
			if(empty($this->serial_number)){
				$this->serial_number = $this->getNextValue($ATMdb);
			}
			
			parent::save($ATMdb);
			
			$this->save_link($ATMdb);
	
			$this->addLotNumber($ATMdb);
			
			// Qty en paramètre est vide, on vérifie si le contenu du flacon a été modifié
			if(empty($qty) && $this->contenancereel_value * pow(10, $this->contenancereel_units) != $this->old_contenancereel * pow(10,$this->old_contenancereel_units)) {
				$qtyKg = $this->contenancereel_value * pow(10, $this->contenancereel_units) - $this->old_contenancereel * pow(10,$this->old_contenancereel_units);
				$qty = $qtyKg * pow(10, -$this->contenancereel_units);
			} else if(!empty($qty)) {
				$this->contenancereel_value = $this->contenancereel_value + $qty;
				parent::save($ATMdb);
			}
			
		}
		
		// Enregistrement des mouvements
		if(!empty($qty) && !$no_destock_dolibarr){
			$this->addStockMouvement($ATMdb,$qty,$description, $destock_dolibarr_only, $fk_prod_to_destock);

		}
		
		//Spécifique Nomadic
		if($conf->clinomadic->enabled){
			$this->updateGaranties();
		}
	}

	function updateGaranties(){
		
		//TODO MAJ garantie client et garantie fournisseur
	}
	
	function addStockMouvement(&$ATMdb,$qty,$description, $destock_dolibarr_only = false, $fk_prod_to_destock=0,$fk_entrepot=1){
		
		if(!$destock_dolibarr_only) {
		
			$stock = new TAssetStock;
			$stock->mouvement_stock($ATMdb, $user, $this->rowid, $qty, $description, $this->rowid);
			
		}

		$this->addStockMouvementDolibarr($this->fk_product,$qty,$description, $destock_dolibarr_only, $fk_prod_to_destock,$fk_entrepot);
	}
	
	function addStockMouvementDolibarr($fk_product,$qty,$description, $destock_dolibarr_only = false, $fk_prod_to_destock=0,$fk_entrepot=1){
		global $db, $user,$conf;
		//echo ' ** 1 ** ';
		// Mouvement de stock standard Dolibarr, attention Entrepôt 1 mis en dur
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$mouvS = new MouvementStock($db);
		// We decrement stock of product (and sub-products)
		// We use warehouse selected for each line
		
		
		$conf->global->PRODUIT_SOUSPRODUITS = false; // Dans le cas asset il ne faut pas de destocke recurssif
		
		/*
		 * Si on est dans un cas où il faut seulement effectuer un mouvement de stock dolibarr, 
		 * on valorise $fk_product qui n'est sinon pas disponible car il correspond à $this->fk_product,
		 * et ce dernier n'est pas disponible car on est dans un cas où l'on n'a pas pu charger l'objet Equipement,
		 * donc pas de $this->fk_product
		 */ 
		$fk_product = $destock_dolibarr_only ? $fk_prod_to_destock : $fk_product;
		
		if($qty > 0) {
			$result=$mouvS->reception($user, $fk_product, $fk_entrepot, $qty, 0, $description);
		} else {
			$result=$mouvS->livraison($user,$fk_product, $fk_entrepot, -$qty, 0, $description);
		}
		//echo (int)$result; exit;
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
	
	function getNextValue($ATMdb){
		
		dol_include_once('core/lib/functions2.lib.php');

		global $db;

		$mask = $this->assetType->masque;

		$ref = get_next_value($db,$mask,'asset','serial_number',' AND fk_asset_type = '.$this->fk_asset_type);

		return $ref;
	}
	
	function addLotNumber(&$ATMdb){
		
		global $conf;
		
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'assetlot WHERE lot_number = "'.$this->lot_number.'"';
		$ATMdb->Execute($sql);

		if($ATMdb->Get_line()){
			return true;
		}
		elseif(!empty($this->lot_number)){
			$lot = new TAssetLot;
			$lot->lot_number = $this->lot_number;
			$lot->entity = $conf->entity;
			$lot->save($ATMdb);
		}
	}
	
	//Spécifique Nomadic
	function retour_pret(&$PDOdb,$fk_entrepot){
		
		//On remet en stock l'équipement
		$this->addStockMouvement($PDOdb, 1, "Retour de prêt",false,0,$fk_entrepot);

		//Réinitialisation des dates de pret et du statut
		$this->etat = 0;
		$this->set_date('date_deb_pret', '');
		$this->set_date('date_fin_pret', '');
		
		$this->save($PDOdb);
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

class TAssetPropaldet extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de commande et les lots 
 */	
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'propaldet');	  
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

class TAssetPropal extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de commande et les lots 
 */	
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'propal');	  
		parent::add_champs('fk_asset','type=chaine;');

		parent::_init_vars();
		
	    parent::start();
	}
}

class TAssetCommande extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de commande et les lots 
 */	
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'commande');	  
		parent::add_champs('fk_asset','type=chaine;');

		parent::_init_vars();
		
	    parent::start();
	}
}

class TAssetFacture extends TObjetStdDolibarr{
/*
 * Liaison entre les lignes de commande et les lots 
 */	
	function __construct() {
		parent::set_table(MAIN_DB_PREFIX.'facture');	  
		parent::add_champs('fk_asset','type=chaine;');

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
		parent::add_champs('libelle,code,reutilisable,masque,gestion_stock','type=chaine;');
		parent::add_champs('entity','type=entier;index;');
		parent::add_champs('contenance_value, contenancereel_value, point_chute', 'type=float;');
		parent::add_champs('contenance_units, contenancereel_units', 'type=entier;');
		parent::add_champs('supprimable','type=entier;');

		parent::_init_vars();
		parent::start();
		$this->TField=array();
		$this->TType=array('chaine'=>'Texte','entier'=>'Entier','float'=>'Float',"liste"=>'Liste','date'=>'Date', "checkbox"=>'Case à cocher');
		$this->TGestionStock = array(
				'UNIT'=>'Unitaire',
				'QUANTITY'=>'Quantitative'
			);
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
	function isUsedByAsset(&$ATMdb){
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
			if(ctype_alnum($s[$i]) || $s[$i] == "_"){
				$r.=$s[$i];			
			}
		} // for
		
		//echo $r; exit;
		return $r;
	}
	
	//Function standard dolibarr pour afficher la structuration des masques
	function info()
    {
    	global $conf,$langs,$db;
	
		$langs->load("admin");
		
		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Proposal"),$langs->transnoentities("Proposal"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes5");
		
		return $tooltip;
    }
		
}

class TAsset_field extends TObjetStd {
	function __construct() { /* declaration */
		parent::set_table(MAIN_DB_PREFIX.'asset_field');
		parent::add_champs('code,libelle','type=chaine;');
		parent::add_champs('type','type=chaine;');
		parent::add_champs('obligatoire','type=entier;');
		parent::add_champs('ordre','type=entier;');
		parent::add_champs('options','type=text;');
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
		$this->fk_asset_type = $fk_asset_type;
		
		
		$this->save($db);
	}
	
	function load(&$ATMdb, $id){
		parent::load($ATMdb, $id);
		$this->TListe = array();
		foreach (explode(";",$this->options) as $key => $value) {
			$this->TListe[] = $value;
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

class TAssetLot extends TObjetStd{
/*
 * Gestion des lot d'équipements 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetlot');
		$this->add_champs('entity','type=entier;');
		$this->add_champs('lot_number', 'type=chaine;');
		
	    $this->start();
	}
}
