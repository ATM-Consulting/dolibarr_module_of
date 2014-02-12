<?php

class TAssetOF extends TObjetStd{
/*
 * Ordre de fabrication d'équipement
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_user','type=entier;');
		$this->add_champs('entity,temps_estime_fabrication,temps_reel_fabrication','type=float;');
		$this->add_champs('ordre,numero,status','type=chaine;');
		$this->add_champs('date_besoin,date_lancement','type=date;');
		
		//clé étrangère : atelier
		//parent::add_champs('fk_asset_workstation','type=entier;index;'); // déporté dans une table à part
		
		parent::add_champs('fk_assetOf_parent','type=entier;index;');
		
	    $this->start();
		
		$this->TOrdre=array(
			'ASAP'=>'Au plut tôt'
			,'TODAY'=>'Dans la journée'
			,'TOMORROW'=> 'Demain'
			,'WEEK'=>'Dans la semaine'
			,'MONTH'=>'Dans le mois'
			
		);
		$this->TStatus=array(
			'DRAFT'=>'Brouillon'
			,'VALID'=>'Validé'
			,'OPEN'=>'Lancé'
			,'CLOSE'=>'Terminé'
		);
		
		$this->workstation=null;
		
		$this->setChild('TAssetOFLine','fk_assetOf');
		$this->setChild('TAssetWorkstationOF','TAssetWorkstationOF');
		$this->setChild('TAssetOF','fk_assetOf_parent');
		
	}
	
	function load(&$db, $id) {
		global $conf;
		
		$res = parent::load($db,$id);
		$this->loadWorkstation($db);
		
		return $res;
	}
	
	function save(&$db) {
		
		parent::save($db);
		
		if($this->numero=='')$this->numero='OF'.str_pad( $this->getId() , 5, '0', STR_PAD_LEFT);
		
		parent::save($db);
	}
	
	//Associe les équipements à l'OF
	function setEquipement(&$ATMdb){
		
		foreach($this->TAssetOFLine as $TAssetOFLine){
			
			$TAssetOFLine->setAsset($ATMdb);	
		}
		
		return true;
	}
	
	function delLine(&$ATMdb,$iline){
		
		$this->TAssetOFLine[$iline]->to_delete=true;
		
	}
	
	//Ajout d'un produit TO_MAKE à l'OF
	function addProductComposition(&$ATMdb, $fk_product, $quantite_to_make=1, $fk_assetOf_line_parent=0){
		
		$Tab = $this->getProductComposition($ATMdb,$fk_product, $quantite_to_make);
		/*echo "<pre>";
		print_r($Tab);
		echo "</pre>";*/
		
		foreach($Tab as $prod) {
			
			$this->addLine($ATMdb, $prod->fk_product, 'NEEDED', $prod->qty * $quantite_to_make,$fk_assetOf_line_parent);
			
		}
		
		return true;
	}
	
	//Retourne les produits NEEDED de l'OF concernant le produit $id_produit
	function getProductComposition(&$ATMdb,$id_product, $quantite_to_make){
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		global $db;	
		
		$Tab=array();
		$product = new Product($db);
		$product->fetch($id_product);
		$TRes = $product->getChildsArbo($product->id);
		
		$this->getProductComposition_arrayMerge($ATMdb,$Tab, $TRes, $quantite_to_make);
		
		return $Tab;
	}
	
	private function getProductComposition_arrayMerge(&$ATMdb,&$Tab, $TRes, $qty_parent=1, $createOF=true) {
		
		foreach($TRes as $row) {
			
			$prod = new stdClass;
			$prod->fk_product = $row[0];
			$prod->qty = $row[1];
			
			if(isset($Tab[$prod->fk_product])) {
				$Tab[$prod->fk_product]->qty += $prod->qty * $qty_parent;
			}
			else {
				$Tab[$prod->fk_product]=$prod;	
			}
			
			if(!empty($row['childs'])) {
				
				if($createOF) {
					$this->createOFifneeded($ATMdb, $prod->fk_product, $prod->qty * $qty_parent);
				}
				else {
					$this->getProductComposition_arrayMerge($Tab, $row['childs'], $prod->qty * $qty_parent);	
				}
			}
		}
		
	} 
	
	/*
	 * Crée une OF si produit composé pas en stock
	 */
	function createOFifneeded(&$ATMdb,$fk_product, $qty_needed) {
		
		$reste = $this->getProductStock($fk_product)-$qty_needed;
		
		if($reste>0) {
			null;
		}
		else {
			
			$k=$this->addChild($ATMdb,'TAssetOF');
			$this->TAssetOF[$k]->status = "DRAFT";
			$this->TAssetOF[$k]->date_besoin = dol_now();
			$this->TAssetOF[$k]->addLine($ATMdb, $fk_product, 'TO_MAKE', abs($qty_needed));
			
		}
		
	}
	/*
	 * retourne le stock restant du produit
	 */
	function getProductStock($fk_product) {
		global $db;
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		$product = new Product($db);
		$product->fetch($fk_product);
		$product->load_stock();
		
		return $product->stock_reel;
		
	}
	
	/*function createCommandeFournisseur($type='externe'){
		global $db,$conf,$user;
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
		
		$id_fourn = $this->getFournisseur();
		
		$cmdFour = new CommandeFournisseur($db);
		$cmdFour->ref_supplier = "";
       	$cmdFour->note_private = "";
        $cmdFour->note_public = "";
        $cmdFour->socid;
		
		return $id_cmd_four;
	}
	
	function getFournisseur(){
		global $db;
		
		return 1;
	}*/
	
	function loadWorkstation(&$ATMdb){
		if(empty($this->workstation)) {
			$this->workstation=new TAssetWorkstation;
			$this->workstation->load($ATMdb, $this->fk_asset_workstation);
		}
	}
	
	//Ajoute une ligne de produit à l'OF
	function addLine(&$ATMdb, $fk_product, $type, $quantite=1,$fk_assetOf_line_parent=0){
		global $user;
		
		$k = $this->addChild($ATMdb, 'TAssetOFLine');
		
		$TAssetOFLine = &$this->TAssetOFLine[$k];
		$TAssetOFLine->fk_assetOf_line_parent = $fk_assetOf_line_parent;
		$TAssetOFLine->entity = $user->entity;
		$TAssetOFLine->fk_product = $fk_product;
		$TAssetOFLine->fk_asset = 0;
		$TAssetOFLine->type = $type;
		$TAssetOFLine->qty = $quantite;
		$TAssetOFLine->qty_used = $quantite;
		
		$idAssetOFLine = $TAssetOFLine->save($ATMdb);
		
		if($type=='TO_MAKE') {
			$this->addProductComposition($ATMdb,$fk_product, $quantite,$idAssetOFLine);
		}
	}
	
	function updateLines(&$ATMdb,$TQty){
		
		foreach($this->TAssetOFLine as $TAssetOFLine){
			$TAssetOFLine->qty_used = $TQty[$TAssetOFLine->getId()];
			$TAssetOFLine->save($ATMdb);
		}
	}
	
	//Finalise un OF => incrémention/décrémentation du stock
	function closeOF(&$ATMdb){
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		foreach($this->TAssetOFLine as $AssetOFLine){
			$asset = new TAsset;
			
			if($AssetOFLine->type == "TO_MAKE"){
				$AssetOFLine->makeAsset($ATMdb,$AssetOFLine->fk_product,$AssetOFLine->qty_used);
			}
		}
	}
	
	function openOF(&$ATMdb){
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		foreach($this->TAssetOFLine as $AssetOFLine){
			$asset = new TAsset;
			
			if($AssetOFLine->type == "NEEDED"){
				//TODO v2 : sélection d'un équipement à associé et décrémenter son stock
				$asset->addStockMouvementDolibarr($AssetOFLine->fk_product,-$AssetOFLine->qty_used,'Utilisation via Ordre de Fabrication');
			}
		}
	}
	
	function getOrdre($ordre='ASAP'){
		
		$TOrdre=array(
			'ASAP'=>'Au plut tôt'
			,'TODAY'=>'Dans la journée'
			,'TOMORROW'=> 'Demain'
			,'WEEK'=>'Dans la semaine'
			,'MONTH'=>'Dans le mois'
			
		);
		
		return $TOrdre[$ordre];
	}
	
	function getStatus($status='DRAFT'){
		$TStatus=array(
			'DRAFT'=>'Brouillon'
			,'VALID'=>'Validé'
			,'OPEN'=>'Lancé'
			,'CLOSE'=>'Terminé'
		);
		
		return $TStatus[$status];
	}
	
	/*function getListeOFEnfants($ATMdb, $Tid, $i) {
		
		global $db;
		
		while($i<count($Tid)) {
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."assetOf";
			$sql.= " WHERE fk_assetOf_parent = ".$Tid[$i];

			$resql = $db->query($sql);
			
			$i++;
			
			if($resql->num_rows>0) {
				
				while($res = $db->fetch_object($resql)) {

					$Tid[] = $res->rowid;
					
				}
				
				$this->getListeOFEnfants($ATMdb, $Tid, $i);
			}
						
		}
		
		unset($Tid[0]);
		
		print_r($Tid);
		exit;
		/*echo "<pre>";
		print_r($Tid);
		echo "</pre>";
		exit;

		$TEnfants = array();
		
		foreach($Tid as $id) {
			
			$assetOf = new TAssetOF;
			$assetOf->load($ATMdb, $id);
			$TabEnfants[] = $assetOf;

		}
		
		return $TabEnfants;
	}*/
	
	function getListeOFEnfants($ATMdb, &$Tid, $id_parent) {
		global $db;
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf";
		$sql.= " WHERE fk_assetOf_parent = ".$id_parent;
		
		$resql = $db->query($sql);
		if($resql->num_rows>0) {
		
			while($res = $db->fetch_object($resql)) {
			
				$Tid[] = $res->rowid;
				$this->getListeOFEnfants($ATMdb, $Tid, $res->rowid);
			
			}
	
		}
		
		/*$TabEnfants = array();
		
		foreach($Tid as $id) {
			$assetOf = new TAssetOF;
			$assetOf->load($ATMdb, $id);
			$TabEnfants[] = $assetOf;
		}
		
		return $TabEnfants;*/
		
	}
}

class TAssetOFLine extends TObjetStd{
/*
 * Ligne d'Ordre de fabrication d'équipement 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf_line');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_assetOf,fk_product,fk_asset,fk_product_fournisseur_price','type=entier;index;');
		$this->add_champs('qty,qty_used','type=float;');
		$this->add_champs('type','type=chaine;');
		
		//clé étrangère
		parent::add_champs('fk_assetOf_line_parent','type=entier;index;');
		
		$this->TType=array('NEEDED','TO_MAKE');
		
		$this->TFournisseurPrice=array();
		
	    $this->start();
		
		$this->setChild('TAssetOFLine','fk_assetOf_line_parent');
	}
	
	//Affecte l'équipement à la ligne de l'OF
	function setAsset(&$ATMdb){
		global $db, $user;	
		include_once 'asset.class.php';
		
		$asset = new TAsset;
		
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."asset WHERE contenance_reel >= ".$this->qty." ORDER BY contenance_reel ASC LIMIT 1";
		$ATMdb->Execute($sql);
		if($ATMdb->Get_line()){
			$idAsset = $ATMdb->Get_field('rowid');
			$asset->load($ATMdb, $idAsset);
			$asset->status = 'indisponible';
		}
		else{
			$asset = $this->makeAsset($ATMdb, $this->fk_product, $this->qty);
		}
				
		$asset->save($ATMdb);
		
		$this->fk_asset = $idAsset;
		$this->save($ATMdb);
		
		return true;
	}
	
	//Utilise l'équipement affecté à la ligne de l'OF
	function makeAsset(&$ATMdb,$fk_product,$qty){
		global $user,$conf;
		include_once 'asset.class.php';
		
		$TAsset = new TAsset;
		$TAsset->fk_soc = '';
		$TAsset->fk_product = $fk_product;
		$TAsset->entity = $user->entity;
		
		/*echo '<pre>';
		print_r($TAsset);
		echo '</pre>';*/
		
		/*
		 * Empêche l'ajout en stock des sous-produit d'un produit composé
		 */
		$varconf = $conf->global->PRODUIT_SOUSPRODUITS;
		$conf->global->PRODUIT_SOUSPRODUITS = NULL;
		$TAsset->save($ATMdb,$user,'Création via Ordre de Fabrication',$qty);
		$conf->global->PRODUIT_SOUSPRODUITS = $varconf;
	}
	
	function load(&$ATMdb, $id) {
		
		parent::load($ATMdb, $id);
		
		$this->loadFournisseurPrice($ATMdb);
		
	}
	
	function loadFournisseurPrice(&$ATMdb) {
		$sql = "SELECT  pfp.rowid,  pfp.fk_soc,  pfp.price,  pfp.quantity, pfp.compose_fourni,s.nom as 'name'
		FROM ".MAIN_DB_PREFIX."product_fournisseur_price pfp LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (pfp.fk_soc=s.rowid)
		WHERE fk_product = ".(int)$this->fk_product;
		
		$ATMdb->Execute($sql);
		
		$interne=new stdClass;
		$interne->rowid=-1;
		$interne->fk_soc=-1;
		$interne->price=0;
		$interne->compose_fourni=0;
		$interne->name='Interne';
		
		$interne2=new stdClass;
		$interne2->rowid=-2;
		$interne2->fk_soc=-1;
		$interne2->price=0;
		$interne2->compose_fourni=1;
		$interne2->name='Interne';
		
		$this->TFournisseurPrice = array_merge(
			array($interne, $interne2)
			,$ATMdb->Get_All()
		);
		
		
	}
	
}
/*
 * Link to product
 */
class TAssetWorkstationProduct extends TObjetStd{
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation_product');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_product, fk_asset_workstation','type=entier;index;');
		$this->add_champs('nb_hour','type=float;'); // nombre d'heure associé au poste de charge et au produit
		
	    $this->start();
	}
	
}

/*
 * Link to OF
 */
class TAssetWorkstationOF extends TObjetStd{
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation_product');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_assetOF, fk_asset_workstation','type=entier;index;');
		$this->add_champs('nb_hour,nb_hour_real','type=float;'); // nombre d'heure associé au poste de charge sur un OF
		
	    $this->start();
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
	
	static function getWorstations($ATMdb) {
		$TWorkstation=array();
		$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."asset_workstation";
		$ATMdb->Execute($sql);
		while($ATMdb->Get_line()){
			$TWorkstation[$ATMdb->Get_field('rowid')]=$ATMdb->Get_field('libelle');
		}
		return $TWorkstation;
	}
	
}
