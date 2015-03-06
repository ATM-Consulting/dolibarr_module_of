<?php

class TAssetOF extends TObjetStd{
/*
 * Ordre de fabrication d'équipement
 * */
 	static $TOrdre=array(
			'ASAP'=>'Au plus tôt'
			,'TODAY'=>'Dans la journée'
			,'TOMORROW'=> 'Demain'
			,'WEEK'=>'Dans la semaine'
			,'MONTH'=>'Dans le mois'
			
		);
 
	static $TStatus=array(
			'DRAFT'=>'Brouillon'
			,'VALID'=>'Valide pour production'
			,'OPEN'=>'Lancé la production'
			,'CLOSE'=>'Terminé'
		);
		
	
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_user,fk_assetOf_parent,fk_soc,fk_commande','type=entier;index;');
		$this->add_champs('entity,temps_estime_fabrication,temps_reel_fabrication','type=float;');
		$this->add_champs('ordre,numero,status','type=chaine;');
		$this->add_champs('date_besoin,date_lancement','type=date;');
		$this->add_champs('note','type=text;');
		
		$this->start();
		
		$this->workstations=array();
		$this->status='DRAFT';
		
		$this->setChild('TAssetOFLine','fk_assetOf');
		$this->setChild('TAssetWorkstationOF','fk_assetOf');
		$this->setChild('TAssetOF','fk_assetOf_parent');
		$this->setChild('TAssetOFControl','fk_assetOf');
		
		$this->date_besoin = time();
		$this->date_lancement = time();
		
		//Tableau d'erreurs
		$this->errors = array();
	}
	
	function load(&$db, $id) {
		global $conf;
		
		$res = parent::load($db,$id);
		
		return $res;
	}
	
	function set_temps_fabrication() {
		$this->temps_estime_fabrication=0;
		$this->temps_reel_fabrication=0;	
			
		foreach($this->TAssetWorkstationOF as $row) {
			
			$this->temps_estime_fabrication+=$row->nb_hour;
			$this->temps_reel_fabrication+=$row->nb_hour_real;
			
			
		}
		
	}
	
	function save(&$db) {
		global $conf;

		$this->set_temps_fabrication();
		$this->entity = $conf->entity;

		if(!empty($conf->global->USE_LOT_IN_OF))
		{
			$this->setLotWithParent($db);
		}
		
		//Sécurité sur la maj de l'objet, si on supprime les lignes d'un OF en mode edit, lors de l'enregistrement les infos sont ré-insert avec un fk_product à 0
		foreach ($this->TAssetOFLine as $k => $ofLine)
		{
			if (!$ofLine->fk_product)
			{
				unset($this->TAssetOFLine[$k]);
			}
		}
		
		parent::save($db);

		if($this->numero=='') {
			$this->numero='OF'.str_pad( $this->getId() , 5, '0', STR_PAD_LEFT);
			$wc = $this->withChild;
			$this->withChild=false;
			parent::save($db);
			$this->withChild=$wc;
		}
	}
	
	function setLotWithParent(&$ATMdb)
	{
		if (count($this->TAssetOFLine) && $this->fk_assetOf_parent)
		{
			$ofParent = new TAssetOF;
			$ofParent->load($ATMdb, $this->fk_assetOf_parent);
			
			foreach($ofParent->TAssetOFLine as $ofLigneParent)
			{
				foreach($this->TAssetOFLine as $ofLigne)
				{
					if($ofLigne->fk_product == $ofLigneParent->fk_product)
					{
						if (empty($this->update_parent))
						{
							$ofLigne->lot_number = $ofLigneParent->lot_number;
							$ofLigne->save($ATMdb);	
						}
						else 
						{
							$ofLigneParent->lot_number = $ofLigne->lot_number;
							$ofLigneParent->save($ATMdb);
						}
					}
				}
			}
		}
	}
	
	//Associe les équipements à l'OF
	function setEquipement(&$ATMdb)
	{
		//pre($this->TAssetOFLine,true);exit;
		foreach($this->TAssetOFLine as $TAssetOFLine)
		{
			$TAssetOFLine->setAsset($ATMdb,$this);	
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
		global $conf;
		//TODO c'est de la merde à refaire
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
			
			if (!empty($conf->global->CREATE_CHILDREN_OF))
			{
				if(!empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT) && !empty($row['childs'])) 
				{
					if(!$createOF) {
						$this->getProductComposition_arrayMerge($Tab, $row['childs'], $prod->qty * $qty_parent);
					}
				}
				
				if ((!empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT) && !empty($row['childs'])) || empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT))
				{
					if($createOF) {
						$this->createOFifneeded($ATMdb, $prod->fk_product, $prod->qty * $qty_parent);
					}
				}
				
			}
		}
		
	} 
	
	/*
	 * Crée une OF si produit composé pas en stock
	 */
	function createOFifneeded(&$ATMdb,$fk_product, $qty_needed) {
		global $conf;

		$reste = $this->getProductStock($fk_product)-$qty_needed;

		if($reste>=0) {
			null;
		}
		else {
			$k=$this->addChild($ATMdb,'TAssetOF');
			$this->TAssetOF[$k]->status = "DRAFT";
			$this->TAssetOF[$k]->fk_soc = $this->fk_soc;
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
	

	//Ajoute une ligne de produit à l'OF
	function addLine(&$ATMdb, $fk_product, $type, $quantite=1,$fk_assetOf_line_parent=0, $lot_number=''){
		global $user, $conf;
		
		$k = $this->addChild($ATMdb, 'TAssetOFLine');
		
		$TAssetOFLine = &$this->TAssetOFLine[$k];
		$TAssetOFLine->fk_assetOf_line_parent = $fk_assetOf_line_parent;
		$TAssetOFLine->entity = $user->entity;
		$TAssetOFLine->fk_product = $fk_product;
		$TAssetOFLine->fk_asset = 0;
		$TAssetOFLine->type = $type;
		$TAssetOFLine->qty_needed = $quantite;
		$TAssetOFLine->qty = $quantite;
		$TAssetOFLine->qty_used = $quantite;
		
		$TAssetOFLine->lot_number = $lot_number;
		
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
	
	/* 
	 * Fonction qui permet de mettre à jour les postes de travail liais à un produit
	 * pour la création d'un OF depuis une fiche produit
	 */
	function addWorkStation($PDOdb, $db, $fk_product) 
	{
		$sql = "SELECT fk_asset_workstation, nb_hour";
		$sql.= " FROM ".MAIN_DB_PREFIX."asset_workstation_product";
		$sql.= " WHERE fk_product = ".$fk_product;
		$resql = $db->query($sql);
		
		if($resql) {
			while($res = $db->fetch_object($resql)) {
				$k = $this->addChild($PDOdb, 'TAssetWorkstationOF');
				$this->TAssetWorkstationOF[$k]->fk_asset_workstation = $res->fk_asset_workstation;
				$this->TAssetWorkstationOF[$k]->nb_hour = $res->nb_hour;
			}
		}
	}
	
	//Finalise un OF => incrémention/décrémentation du stock
	function closeOF(&$ATMdb, $conf = null)
	{
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		if (!empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) $fk_entrepot = $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED;
		else $fk_entrepot = $asset->fk_entrepot;
		
		foreach($this->TAssetOFLine as $AssetOFLine)
		{
			$asset = new TAsset;
			
			if($AssetOFLine->type == "TO_MAKE")
			{
				$objAsset = $AssetOFLine->makeAsset($ATMdb, $this, $AssetOFLine->fk_product, $AssetOFLine->qty, 0, $AssetOFLine->lot_number);
				TAsset::set_element_element($AssetOFLine->getId(), 'TAssetOFLine', $objAsset->getId(), 'TAsset');
			} 
			else 
			{
				// TODO l'attribut qty_used destock une nouvelle fois sur 1 équipement mais ne prend pas en compte si l'équipement est cumulable, périsable ...
				// La boucle permet de reprendre tous les équipements utilisés pour la fabrication, mais on ne sais pas quelle qté a été utilisé
				$ids_asset = $AssetOFLine->getElementElement();
				foreach ($ids_asset as $id_asset)
				{
					$asset->load($ATMdb, $id_asset);
					$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$this->numero.' - Equipement : '.$asset->serial_number, $AssetOFLine->qty - $AssetOFLine->qty_used, $asset->rowid == 0 ? true : false, $asset->rowid == 0 ? $AssetOFLine->fk_product : 0, false, $fk_entrepot);
				}
				
			}
		}
	}
	
	function openOF(&$ATMdb){
		global $db, $user;
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		dol_include_once("fourn/class/fournisseur.product.class.php");
		dol_include_once("fourn/class/fournisseur.commande.class.php");
		
		foreach($this->TAssetOFLine as $AssetOFLine){
			$asset = new TAsset;
			$asset->load($ATMdb, $AssetOFLine->fk_asset);

			if($AssetOFLine->type == "NEEDED"){
				$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$this->numero.' - Equipement : '.$asset->serial_number,-$AssetOFLine->qty_used);
			}

		}
	}
	
	private function getEnfantsDirects() {
		
		global $db;
		
		$TabIdEnfants = array();
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf";
		$sql.= " WHERE fk_assetOf_parent = ".$this->rowid;
		
		$resql = $db->query($sql);
		
		while($res = $db->fetch_object($resql)) {
			$TabIdEnfants[] = $res->rowid;
		}
		
		return $TabIdEnfants;
		
	}
	
	private function addCommandeFourn(&$ATMdb,$ofLigne, $resultatSQL) {

		global $db, $user;
		dol_include_once("fourn/class/fournisseur.commande.class.php");		
		
		// On cherche s'il existe une commande pour ce fournisseur
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE fk_soc = ".$resultatSQL->fk_soc;
		$sql.= " AND fk_statut = 0"; //uniquement brouillon
		$sql.= " ORDER BY rowid DESC";
		$sql.= " LIMIT 1";
		$resql = $db->query($sql);
		
		$res = $db->fetch_object($resql);

		if($res) { // Il existe une commande, on la charge
			$com = new CommandeFournisseur($db);
			$com->fetch($res->rowid);
		} else { // Il n'existe aucune commande pour ce fournisseur donc on en crée une nouvelle
			$com = new CommandeFournisseur($db);
			$com->socid = $resultatSQL->fk_soc;
			$com->create($user);
		}
		
		// On cherche si ce produit existe déjà dans la commande, si oui, : "updateline"
		foreach($com->lines as $line) {
			if($line->fk_product == $resultatSQL->fk_product) {
				$com->updateline($line->id, $line->desc, $line->subprice, $line->qty+$ofLigne->qty, $line->remise_percent, $line->tva_tx);
				$done = true;
				break;
			}
		}
		
		if(!$done) {
			
			// Si le produit n'existe pas déjà dans la commande, on l'ajoute à cette commande
			$com->addline($desc, $resultatSQL->price/$resultatSQL->quantity, $ofLigne->qty, $txtva, 0, 0, $resultatSQL->fk_product, $resultatSQL->rowid);

		}
		
		//Création association element_element entre la commande fournisseur et l'OF
		$this->addElementElement($ATMdb,$com);
	}

	function delete(&$PDOdb){
		
		parent::delete($PDOdb);
		
		$this->delElementElement($PDOdb);
		
	}

	function addElementElement(&$ATMdb,&$commandeFourn){
		
		$TIdCommandeFourn = $this->getElementElement($ATMdb);

		if(!in_array($commandeFourn->id, $TIdCommandeFourn)){
				
			$ATMdb->Execute("INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source,fk_target,sourcetype,targettype) 
								VALUES (".$this->getId().",".$commandeFourn->id.",'ordre_fabrication','order_supplier')");
		}
		
	}
	
	function delElementElement(&$ATMdb){
		
		$ATMdb->Execute("DELETE FROM ".MAIN_DB_PREFIX."element_element 
						 WHERE sourcetype = 'ordre_fabrication'
						 	AND targettype = 'order_supplier'
						 	AND fk_source = ".$this->getId());
	}
	
	function getElementElement(&$ATMdb){
		
		$TIdCommandeFourn = array();
		
		$sql = "SELECT fk_target 
				FROM ".MAIN_DB_PREFIX."element_element 
				WHERE fk_source = ".$this->getId()." 
					AND sourcetype = 'ordre_fabrication' 
					AND targettype = 'order_supplier'";
		
		$ATMdb->Execute($sql);
		
		while($ATMdb->Get_line()){
			$TIdCommandeFourn[] = $ATMdb->Get_field('fk_target');
		}
		
		return $TIdCommandeFourn;

	}
	
	function createOfAndCommandesFourn(&$ATMdb) {
		global $db, $user;
		
		dol_include_once("fourn/class/fournisseur.commande.class.php");
		
		$TabOF = array();
		$TabOF[] = $this->rowid;
		$this->getListeOFEnfants($ATMdb, $TabOF);
		
		// Boucle pour chaque OF de l'arbre
		foreach($TabOF as $idOf){
			
			// On charge l'OF
			$assetOF = new TAssetOF;
			$assetOF->load($ATMdb, $idOf);
			
			// Boucle pour chaque produit de l'OF
			foreach($assetOF->TAssetOFLine as $ofLigne) {
				//pre($ofLigne,true);
				// On cherche le produit "TO_MAKE"
				if($ofLigne->type == "TO_MAKE") {
					
					//pre($ofLigne,true); exit;

					if($ofLigne->fk_product_fournisseur_price > 0) { // Fournisseur externe
					
						// On récupère la ligne prix fournisseur correspondante
						$sql = "SELECT rowid, fk_soc, fk_product, price, compose_fourni, quantity, ref_fourn";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
						$sql.= " WHERE rowid = ".$ofLigne->fk_product_fournisseur_price;
						$resql = $db->query($sql);

						$res = $db->fetch_object($resql);
						
						// Si fabrication interne
						if($res->compose_fourni) {
						
							// On charge le produit "TO_MAKE"
							$prod = new Product($db);
							$prod->fetch($ofLigne->fk_product);
							$prod->load_stock();

							$stockProd = 0;
							
							// On récupère son stock
							foreach($prod->stock_warehouse as $stock) {
								$stockProd += $stock->real;
							}
							
							// S'il y a suffisemment de stock, on destocke
							// Sinon, commande fournisseur :
							if($stockProd < $ofLigne->qty_needed) {
								
								$this->addCommandeFourn($ATMdb,$ofLigne, $res);

							} 
							else { // Suffisemment de stock, donc destockage :
								$assetOF->openOF($ATMdb);
							}
						}
						elseif(!$res->compose_fourni) { //Commande Fournisseur
						
							$this->addCommandeFourn($ATMdb,$ofLigne, $res);

							// On récupère les OF enfants pour les supprimer
							$TabIdEnfantsDirects = $assetOF->getEnfantsDirects();

							foreach($TabIdEnfantsDirects as $idOF) {
							
								$assetOF->removeChild("TAssetOF", $idOF);
							}
							
							//Suppression des lignes NEEDED puisque inutiles
							$assetOF->delLineNeeded($ATMdb);
							$assetOF->unsetChildDeleted = true;
							
							$assetOF->save($ATMdb);
							
							// On casse la boucle
							break;

						}

					} 
					else { // Fournisseur interne (Bourguignon)
					
						if($ofLigne->fk_product_fournisseur_price == -1) { // Sortie de stock, kill OF enfants
							
							$TabIdEnfantsDirects = $assetOF->getEnfantsDirects();
							
							foreach($TabIdEnfantsDirects as $idOF) {
							
								$assetOF->removeChild("TAssetOF", $idOF);
							}

							$assetOF->save($ATMdb);
							
							// On casse la boucle
							break;

						}
						elseif($ofLigne->fk_product_fournisseur_price == -2){ // Fabrication interne
							$prod = new Product($db);
							$prod->fetch($ofLigne->fk_product);
							$prod->load_stock();
							
							$stockProd = 0;
							
							// On récupère son stock
							foreach($prod->stock_warehouse as $stock) {
								$stockProd += $stock->real;
							}	
							
							// S'il y a sufisemment de stock, on destocke
							if($stockProd >= $ofLigne->qty_needed) {
								$assetOF->openOF($ATMdb);
							}
													
						}
						
					}
					
				}

			}
			
		}

	}
	
	function delLineNeeded(&$ATMdb){
		
		foreach($this->TAssetOFLine as $k=>$ofLigne){

			if($ofLigne->type == "NEEDED"){
				$this->delLine($ATMdb, $k);
			}
		}
		
	}
	
	static function ordre($ordre='ASAP'){
		
		
		return TAssetOF::$TOrdre[$ordre];
	}
	
	
	function getListeOFEnfants(&$ATMdb, &$Tid, $id_parent=null, $recursive = true) {
			
		if(is_null($id_parent))$id_parent = $this->getId();
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf";
		$sql.= " WHERE fk_assetOf_parent = ".$id_parent;
		$sql.= " ORDER BY fk_assetOf_parent, rowid";
		
		$Tab = $ATMdb->ExecuteAsArray($sql);
		foreach($Tab as $row) {
			$Tid[] = $row->rowid;
			if ($recursive) $this->getListeOFEnfants($ATMdb, $Tid, $row->rowid);
		}
				
	}

	static function status($status='DRAFT'){
		
			
		return  TAssetOF::$TStatus[$status];
	}
	
	function getCanBeParent(&$PDOdb) {
		
		$sql="SELECT rowid, numero FROM ".MAIN_DB_PREFIX."assetOf 
		WHERE rowid NOT IN (".$this->getId().") AND status='DRAFT'";
		$Tab = $PDOdb->ExecuteAsArray($sql);
		$TCombo=array();
		foreach($Tab as $row) {
			$TCombo[$row->rowid] = $row->numero;
		}
		
		return $TCombo;
		
	}
	
	function getLastId(&$PDOdb){
		$PDOdb->Execute('SELECT rowid FROM '.MAIN_DB_PREFIX.'assetOf ORDER BY rowid DESC LIMIT 1');
		$PDOdb->Get_line();
		
		return $PDOdb->Get_field('rowid');
	}
	
	/**
	 * Retourne un tableau contenant les identifaints des OF créés à partir de la commande dont le rowid est égal à $id_command
	 * @param int $id_command
	 * @return array $TID_OF_command
	 */
	static function getTID_OF_command($id_command) {
		
		global $db;
		$TID_OF_command = array();
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf of";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."element_element ee ON (of.rowid = ee.fk_source AND ee.sourcetype = 'ordre_fabrication' AND ee.targettype = 'order_supplier')";
		$sql.= " WHERE ee.fk_target = ".$id_command;
		$resql = $db->query($sql);
		
		while($res = $db->fetch_object($resql)) {
			$TID_OF_command[] = $res->rowid;
		}
		
		return $TID_OF_command;
	}

	function checkLotIsFill()
	{
		$fill = true;
		foreach ($this->TAssetOFLine as $OFLine)
		{
			if ($OFLine->type == 'TO_MAKE') 
			{
				if (empty($OFLine->lot_number)) 
				{
					$fill = false;
					break;
				}
				
				if ($OFLine->fk_product_fournisseur_price <= 0) $fill = $this->checkChildrenLotIsFill($OFLine);
			}
		}
			
		return $fill;
	}

	function checkChildrenLotIsFill($line)
	{
		global $db;
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		$fill = true;
		$product = new Product($db);
		$children = $product->getChildsArbo($line->fk_product);
		
		foreach ($this->TAssetOFLine as $OFLine)
		{
			if ($OFLine->type == 'NEEDED' && isset($children[$OFLine->fk_product]))
			{
				if (empty($OFLine->lot_number)) 
				{
					$fill = false;
					break;
				}
			}
		}
		
		
		return $fill;
	}
	
	function checkCommandeFournisseur(&$ATMdb)
	{
		global $db;
		
		$res = true;
		$Tid = $this->getElementElement($ATMdb);
		
		foreach ($Tid as $id)
		{
			$cmdf = new CommandeFournisseur($db);
			$cmdf->fetch($id);
			
			//4 = livraison partielle # 5 = livraison total
			if (!in_array($cmdf->statut, array(4,5)))
			{
				$res = false;
				break;
			}
		}
		
		return $res;
	}
	
	/*
	 * Fonction qui vérifie si la quantité des équipement est suffisante pour lancer la production
	 * Alimente $this->errors 
	 * return true if OK else false 
	 */
	function checkQtyAsset($PDOdb, $conf)
	{
		global $db;
		
		$res = true;
		foreach($this->TAssetOFLine as $TAssetOFLine)
		{
			if ($TAssetOFLine->type != "NEEDED") continue;
			
			//
			$completeSql = '';
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'asset';
			
			$is_cumulate = TAsset_type::getIsCumulate($PDOdb, $TAssetOFLine->fk_product);
			$is_perishable = TAsset_type::getIsPerishable($PDOdb, $TAssetOFLine->fk_product);
		
			if ($is_cumulate)
			{
				$sql.= ' WHERE contenancereel_value > 0';
				if (is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, date_cre ASC, contenancereel_value ASC';
				else $completeSql = ' ORDER BY date_cre ASC, contenancereel_value ASC';
			}
			else 
			{
				$sql.= ' WHERE contenancereel_value >= '.$this->qty;
				if ($is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, contenancereel_value ASC, date_cre ASC LIMIT 1';
				else $completeSql = ' ORDER BY contenancereel_value ASC, date_cre ASC LIMIT 1';
			}
	
			$sql.= ' AND fk_product = '.$TAssetOFLine->fk_product;
			
			if ($conf->global->USE_LOT_IN_OF)
			{
				$sql .= ' AND lot_number = "'.$this->lot_number.'"';
			}
			
			$sql.= $completeSql;
		
			$PDOdb->Execute($sql);
			
			$qty_needed = $TAssetOFLine->qty;
			$qty_cumulate = 0;
			$break=false;
			
			while ($PDOdb->Get_line())
			{
				$idAsset = $PDOdb->Get_field('rowid');
				$asset->load($ATMdb, $idAsset);
				
				$qty_cumulate += $asset->contenancereel_value;
				
				//On a suffisament en stock, break = true donc pas de msg d'erreur
				if ($qty_cumulate - $qty_needed >= 0)
				{
					$break = true;
				}

				if ($break) break;
			}
			
			if(!$break) 
			{
				$product = new Product($db);
				$product->fetch($TAssetOFLine->fk_product);
				
				if($conf->global->USE_LOT_IN_OF)
				{
					$res = false;
					$this->errors[] = "La quantité d'équipement pour le produit ".$product->label." dans le lot n°".$TAssetOFLine->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
				}
				else
				{
					$res = false;
					$this->errors[] = "Aucun équipement disponible pour le produit ".$product->label;
				}
			}
			
			/*
			$sql = "SELECT rowid 
				FROM ".MAIN_DB_PREFIX."asset 
				WHERE contenancereel_value >= ".$TAssetOFLine->qty."
					AND fk_product = ".$TAssetOFLine->fk_product;
		
			if($conf->global->USE_LOT_IN_OF)
			{
				$sql .= ' AND lot_number = "'.$TAssetOFLine->lot_number.'"';
			}
			
			$sql .= " ORDER BY contenancereel_value ASC LIMIT 1";
			
			$PDOdb->Execute($sql);
			
			if(!$PDOdb->Get_line()) 
			{
				$product = new Product($db);
				$product->fetch($TAssetOFLine->fk_product);
				
				if($conf->global->USE_LOT_IN_OF)
				{
					$res = false;
					$this->errors[] = "La quantité d'équipement pour le produit ".$product->label." dans le lot n°".$TAssetOFLine->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
				}
				else
				{
					$res = false;
					$this->errors[] = "Aucun équipement disponible pour le produit ".$product->label;
				}
			}
			*/
		}
		
		return $res;
	}

	function updateControl(&$ATMdb, $subAction)
	{
		if ($subAction == 'addControl')
		{
			$TControl =  __get('TControl', array());

			foreach ($TControl as $fk_control)
			{
				$ofControl = new TAssetOFControl;
				$ofControl->fk_assetOf = $this->getId();
				$ofControl->fk_control = $fk_control;
				$ofControl->response = '';
				$this->TAssetOFControl[] = $ofControl;
				
			}
			
			$this->save($ATMdb);
			setEventMessage("Contrôle ajouté");
		}
		elseif ($subAction == 'updateControl')
		{
			$TControlDelete = __get('TControlDelete', array());
			$TResponse = __get('TControlResponse', false);
			foreach ($this->TAssetOFControl as $ofControl)
			{
				//Si la ligne est marqué à supprimer alors on delete l'info et on passe à la suite
				if (in_array($ofControl->getId(), $TControlDelete))
				{
					$ofControl->delete($ATMdb);
					continue;
				}
				
				//Toutes les valeurs sont envoyées sous forme de tableau
				$val = !empty($TResponse[$ofControl->getId()]) ? implode(',', $TResponse[$ofControl->getId()]) : '';
				$ofControl->response = $val;
				$ofControl->save($ATMdb);
			}
			
			setEventMessage("Modifications enregistrées");
		}
	}
	
	function generate_visu_control_value($fk_control, $type, $value, $name)
	{
		$res = '';
		switch ($type) {
			case 'text':
				$res = '<input name="'.$name.'" type="text" style="width:99%;" maxlength="255" value="'.$value.'" />';
				break;
				
			case 'num':
				$res = '<input name="'.$name.'" type="number" style="width:55px" value="'.$value.'" min="0" />';
				break;
				
			case 'checkbox':
				$res = '<input name="'.$name.'" type="checkbox" '.($value ? 'checked="checked"' : '').' value="1" />&nbsp;&nbsp;';
				break;
			
			case 'checkboxmultiple':
				$ATMdb = new TPDOdb;
				$values = explode(',', $value);
				$control = new TAssetControl;
				$control->load($ATMdb, $fk_control);
				
				foreach ($control->TAssetControlMultiple as $controlValue)
				{
					$res.= '<span style="border:1px solid #A4B2C3;padding:0 4px 0 2px;">';
					$res.= '<input name="'.$name.'" style="vertical-align:middle" '.(in_array($controlValue->getId(), $values) ? 'checked="checked"' : '').' type="checkbox" value="'.$controlValue->getId().'" />';
					$res.= '&nbsp;'.$controlValue->value.'</span>&nbsp;&nbsp;&nbsp;';
				}
				
				$res = trim($res);
				break;
		}
		
		return $res;
	}

	function getControlPDF(&$ATMdb)
	{
		$res = array();
		
		foreach ($this->TAssetOFControl as $ofControl)
		{
			$control = new TAssetControl;
			$control->load($ATMdb, $ofControl->fk_control);
			
			switch ($control->type) {
				case 'text':
				case 'num':
					$res[] = array(
						'question'=>utf8_decode($control->question)
						,'response'=>$ofControl->response
					);
					break;
									
				case 'checkbox':
					$res[] = array(
						'question'=>utf8_decode($control->question)
						,'response'=>$ofControl->response ? 'Oui' : 'Non'
					);
					break;
				
				case 'checkboxmultiple':
					$res2 = '';
					foreach ($control->TAssetControlMultiple as $controlVal)
					{
						$res2 .= $controlVal->value.', ';
					}
					
					$res[] = array(
						'question'=>utf8_decode($control->question)
						,'response'=>rtrim($res2, ', ')
					);
					break;
			}
		}
		
		return $res;
	}
	
}

class TAssetOFLine extends TObjetStd{
/*
 * Ligne d'Ordre de fabrication d'équipement 
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf_line');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_assetOf,fk_product,fk_product_fournisseur_price','type=entier;index;');
		$this->add_champs('qty_needed,qty,qty_used','type=float;');
		$this->add_champs('type,lot_number','type=chaine;');
		
		//clé étrangère
		parent::add_champs('fk_assetOf_line_parent','type=entier;index;');
		
		$this->TType=array('NEEDED','TO_MAKE');
		
		$this->TFournisseurPrice=array();
		
	    $this->start();
		$this->setChild('TAssetOFLine','fk_assetOf_line_parent');
	}
	
	//Affecte l'équipement à la ligne de l'OF
	function setAsset(&$ATMdb,&$AssetOf)
	{
		global $db, $user, $conf;	
		
		include_once 'asset.class.php';
		
		$ATMdb2 = new TPDOdb;
		
		$asset = new TAsset;
		$asset->fk_product = $this->fk_product;
		
		$completeSql = '';
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'asset';
		
		$is_cumulate = TAsset_type::getIsCumulate($ATMdb, $this->fk_product);
		$is_perishable = TAsset_type::getIsPerishable($ATMdb, $this->fk_product);
		
		//Si type equipement est cumulable alors on destock 1 ou +sieurs équipements jusqu'à avoir la qté nécéssaire
		if ($is_cumulate)
		{
			$sql.= ' WHERE contenancereel_value > 0';
			if (is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, date_cre ASC, contenancereel_value ASC';
			else $completeSql = ' ORDER BY date_cre ASC, contenancereel_value ASC';
		}
		else 
		{
			$sql.= ' WHERE contenancereel_value >= '.$this->qty;
			if ($is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, contenancereel_value ASC, date_cre ASC LIMIT 1';
			else $completeSql = ' ORDER BY contenancereel_value ASC, date_cre ASC LIMIT 1';
		}

		$sql.= ' AND fk_product = '.$this->fk_product;
		
		if ($conf->global->USE_LOT_IN_OF)
		{
			$sql .= ' AND lot_number = "'.$this->lot_number.'"';
		}
		
		$sql.= $completeSql;
		
		$ATMdb2->Execute($sql);

		if($this->type == "NEEDED" && $AssetOf->status == "OPEN")
		{
			$nbRecord = $ATMdb2->Get_Recordcount();
			$mvmt_stock_already_done = $nbRecord > 0 ? true : false;
			$qty_needed = $this->qty;
			$break=false;
			
			while ($ATMdb2->Get_line())
			{
				$idAsset = $ATMdb2->Get_field('rowid');
				$asset->load($ATMdb, $idAsset);
				
				if ($asset->contenancereel_value - $qty_needed >= 0)
				{
					$qty_to_destock = $qty_needed;
					$break = true;
				}
				else 
				{
					$qty_to_destock = $asset->contenancereel_value;
					$qty_needed -= $asset->contenancereel_value;
				}
					
				TAsset::set_element_element($this->getId(), 'TAssetOFLine', $asset->getId(), 'TAsset');
				
				if (!empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) $fk_entrepot = $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED;
				else $fk_entrepot = $asset->fk_entrepot;
				
				$asset->status = 'indisponible';
				//On affiche aussi l'ID de l'équipement dans la description pcq le serial_number peut être vide
				$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$AssetOf->numero.' - Equipement : '.$asset->getId().' - '.$asset->serial_number, -$qty_to_destock, false, 0, false, $fk_entrepot);
			
				if ($break) break;
			}
			
			if ($nbRecord <= 0)
			{
				if($conf->global->USE_LOT_IN_OF)
				{
					$AssetOf->errors[] = "La quantité d'équipement pour le produit ID ".$this->fk_product." dans le lot n°".$this->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
				}
				else
				{
					$product = new Product($db);
					$product->fetch($this->fk_product);
					$AssetOf->errors[] = "Aucun équipement disponible pour le produit ".$product->label;
				}
			}
			
			if(!$mvmt_stock_already_done) 
			{
				$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$AssetOf->numero.' - Equipement : '.$asset->serial_number, -$this->qty, true, $this->fk_product, false, $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED);
			}
			
			/*
			if($ATMdb->Get_line())
			{					
				$mvmt_stock_already_done = true;
				
				$idAsset = $ATMdb->Get_field('rowid');
				$asset->load($ATMdb, $idAsset);
				
				$asset->status = 'indisponible';
				$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$AssetOf->numero.' - Equipement : '.$asset->serial_number, -$this->qty, false, 0, false, $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED);
			}
			elseif($conf->global->USE_LOT_IN_OF)
			{
				$AssetOf->errors[] = "La quantité d'équipement pour le produit ID ".$this->fk_product." dans le lot n°".$this->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
			}
			else
			{
				$product = new Product($db);
				$product->fetch($this->fk_product);
				$AssetOf->errors[] = "Aucun équipement disponible pour le produit ".$product->label;
			}
			
			if(!$mvmt_stock_already_done) 
			{
				$asset->save($ATMdb,$user,'Utilisation via Ordre de Fabrication n°'.$AssetOf->numero.' - Equipement : '.$asset->serial_number, -$this->qty, true, $this->fk_product, false, $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED);
			}
			*/
		}
		
		$this->fk_asset = $idAsset;
		$this->save($ATMdb, $conf);	

		return true;
	}
	
	//Utilise l'équipement affecté à la ligne de l'OF
	function makeAsset(&$ATMdb, &$AssetOf, $fk_product, $qty, $idAsset = 0, $lot_number = '')
	{
		global $user,$conf;
		include_once 'asset.class.php';

		$TAsset = new TAsset;
		$TAsset->fk_soc = '';
		$TAsset->fk_product = $fk_product;
		$TAsset->entity = $user->entity;
		$TAsset->lot_number = $lot_number;
		$TAsset->fk_asset_type = $TAsset->get_asset_type($ATMdb,$fk_product);
		$TAsset->load_liste_type_asset($ATMdb);
		$TAsset->load_asset_type($ATMdb);
		
		if($conf->global->USE_LOT_IN_OF)
		{
			$TAsset->lot_number = $this->lot_number;
		}
		
		if (!empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) $fk_entrepot = $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE;
		else $fk_entrepot = $TAsset->fk_entrepot;
			
		$TAsset->save($ATMdb); //Save une première fois pour avoir le serial_number + 2ème save pour mvt de stock	
		$TAsset->save($ATMdb, $user, 'Création via Ordre de Fabrication n°'.$AssetOf->numero." - Equipement : ".$TAsset->serial_number, $qty, false, 0, false, $fk_entrepot);

		return $TAsset;
	}
	
	function getWorkstationsPDF(&$db)
	{
		$res = '';
		if (count($this->workstations) <= 0)
			return $res;
		
		$sql = 'SELECT libelle FROM '.MAIN_DB_PREFIX.'asset_workstation WHERE rowid IN ('.implode(',', $this->workstations).')';
		$resql = $db->query($sql);
		
		while ($r = $db->fetch_object($resql)) 
		{
			$res .= $r->libelle.', ';
		}
		
		$res = rtrim($res, ', ');
		
		return $res;
	}
	
	function load(&$ATMdb, $id) {
		parent::load($ATMdb, $id);
		$this->workstations = $this->get_workstations($ATMdb);
		
		$this->loadFournisseurPrice($ATMdb);
	}
	
	function delete(&$db)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
		$db->Execute($sql);
		parent::delete($db);
	}
	
	function set_workstations(&$ATMdb, $Tworkstations)
	{
		if (empty($Tworkstations)) return false;
	
		$this->workstations = array();
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
		$ATMdb->Execute($sql);
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'element_element (';
		$sql.= 'fk_source, sourcetype, fk_target, targettype';
		$sql.= ') VALUES ';
		
		$save = false;
		foreach ($Tworkstations as $id_workstation) 
		{
			if ($id_workstation <= 0) continue;
			
			$this->workstations[] = $id_workstation;
			$save = true;
			
			$sql.= '(';
			$sql.= (int) $this->rowid.',';
			$sql.= $ATMdb->quote('tassetofline').',';
			$sql.= (int) $id_workstation.',';
			$sql.= $ATMdb->quote('tassetworkstation');
			$sql.= '),';
		}
		
		if ($save)
		{
			$sql = rtrim($sql, ',');
			$ATMdb->Execute($sql);
		}
	}
	
	function get_workstations(&$ATMdb)
	{		
		$res = array();
		
		$sql.= 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= ' WHERE fk_source = '.(int) $this->rowid;
		$sql.= ' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
		
		$ATMdb->Execute($sql);
		while ($ATMdb->Get_line()) $res[] = $ATMdb->Get_field('fk_target');
		
		return $res;
	}	
	
	function visu_checkbox_workstation(&$db, &$of, &$form, $name)
	{
		$include = array();
		
		$sql = 'SELECT libelle, rowid FROM '.MAIN_DB_PREFIX.'asset_workstation WHERE rowid IN (SELECT fk_asset_workstation FROM '.MAIN_DB_PREFIX.'asset_workstation_of WHERE fk_assetOf = '.(int) $of->rowid.')';
		$resql = $db->query($sql);
		
		$res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($r = $db->fetch_object($resql)) 
		{
			$res .= '<p style="margin:4px 0">'.$form->checkbox1($r->libelle, $name, $r->rowid, (in_array($r->rowid, $this->workstations) ? true : false), 'style="vertical-align:text-bottom;"', '', '', 'case_before') . '</p>';
		}
		
		return $res;
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
	
	function save(&$db) {
		
		global $conf;

		$this->entity = $conf->entity;
		
		parent::save($db);

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
		$this->add_champs('nb_hour_prepare,nb_hour_manufacture,nb_hour,rang','type=float;'); // nombre d'heure associé au poste de charge et au produit
		
		$this->start();
		
		$this->nb_hour=0;
		$this->rang=0;
	}
	
}

/*
 * Link to OF
 */
class TAssetWorkstationOF extends TObjetStd{
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation_of');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_assetOf, fk_asset_workstation','type=entier;index;');
		$this->add_champs('nb_hour,nb_hour_real','type=float;'); // nombre d'heure associé au poste de charge sur un OF
		
	    $this->start();
		
		$this->ws = new TAssetWorkstation;
		
	}
	
	function delete(&$db)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetworkstationof" AND targettype = "user"';
		$db->Execute($sql);
		
		parent::delete($db);
	}
	
	function set_users(&$ATMdb, $Tusers)
	{
		if (empty($Tusers)) return false;
		
		$this->users = array();
		
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetworkstationof" AND targettype = "user"';
		$ATMdb->Execute($sql);
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'element_element (';
		$sql.= 'fk_source, sourcetype, fk_target, targettype';
		$sql.= ') VALUES ';
		
		$save = false;
		foreach ($Tusers as $id_user) 
		{
			if ($id_user <= 0) continue;
				
			$this->users[] = $id_user;
			$save = true;
			
			$sql.= '(';
			$sql.= (int) $this->rowid.',';
			$sql.= $ATMdb->quote('tassetworkstationof').',';
			$sql.= (int) $id_user.',';
			$sql.= $ATMdb->quote('user');
			$sql.= '),';
		}
		
		if ($save)
		{
			$sql = rtrim($sql, ',');
			$ATMdb->Execute($sql);
		}
	}
	
	function get_users(&$ATMdb)
	{		
		$res = array();
		
		$sql.= 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= ' WHERE fk_source = '.(int) $this->rowid;
		$sql.= ' AND sourcetype = "tassetworkstationof" AND targettype = "user"';
		
		$ATMdb->Execute($sql);
		while ($ATMdb->Get_line()) $res[] = $ATMdb->Get_field('fk_target');
		
		return $res;
	}

	function getUsersPDF(&$db, &$ATMdb)
	{
		$res = '';
		$ids_user = $this->get_users($ATMdb);
		
		if(count($ids_user) <= 0) return $res;
		
		$sql = 'SELECT lastname, firstname FROM '.MAIN_DB_PREFIX.'user WHERE rowid IN ('.implode(',', $ids_user).')';
		$resql = $db->query($sql);
		
		while ($r = $db->fetch_object($resql)) 
		{
			$res .= $r->lastname.' '.$r->firstname.', ';
		}
		
		$res = rtrim($res, ', ');
		
		return $res;
	}
	
	function load(&$ATMdb, $id) 
	{	
		parent::load($ATMdb,$id);
		$this->users = $this->get_users($ATMdb);
		
		if($this->fk_asset_workstation >0)
		{
			$this->ws->load($ATMdb, $this->fk_asset_workstation);
		}
	}
	
	function visu_checkbox_user(&$db, &$form, $group, $name)
	{
		$include = array();
		
		$sql = 'SELECT u.lastname, u.firstname, uu.fk_user FROM '.MAIN_DB_PREFIX.'usergroup_user uu INNER JOIN '.MAIN_DB_PREFIX.'user u ON (uu.fk_user = u.rowid) WHERE uu.fk_usergroup = '.(int) $group;
		$resql = $db->query($sql);
		
		$res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($r = $db->fetch_object($resql)) 
		{
			$res .= '<p style="margin:4px 0">'.$form->checkbox1($r->lastname.' '.$r->firstname, $name, $r->fk_user, (in_array($r->fk_user, $this->users) ? true : false), 'style="vertical-align:text-bottom;"', '', '', 'case_before').'</p>';
		}
		
		return $res;
	}
	
}



class TAssetWorkstation extends TObjetStd{
/*
 * Atelier de fabrication d'équipement
 * */
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation');
    	$this->TChamps = array(); 	  
		$this->add_champs('entity,fk_usergroup','type=entier;index;');
		$this->add_champs('libelle','type=chaine;');
		$this->add_champs('nb_hour_prepare,nb_hour_manufacture,nb_hour_max','type=float;'); // charge maximale du poste de travail
		
	    $this->start();
	}
	
	function save(&$ATMdb) {
		global $conf;
		
		$this->entity = $conf->entity;
		
		parent::save($ATMdb);
	}
	
	static function getWorstations(&$ATMdb) 
	{
		global $conf;
		
		$TWorkstation=array();
		$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."asset_workstation WHERE entity=".$conf->entity;
		
		$ATMdb->Execute($sql);
		while($ATMdb->Get_line())
		{
			$TWorkstation[$ATMdb->Get_field('rowid')]=$ATMdb->Get_field('libelle');
		}
		
		return $TWorkstation;
	}
	
}


class TAssetControl extends TObjetStd
{
	static $TType=array(
			'text'=>'Texte libre'
			,'checkbox'=>'Réponse oui / non'
			,'num'=> 'Réponse numérique'
			,'checkboxmultiple'=>'Réponse multiple'
			
		);
	
	function __construct() 
	{
		$this->set_table(MAIN_DB_PREFIX.'asset_control');
    	$this->TChamps = array(); 	  
		$this->add_champs('libelle,type,question','type=chaine;');
		
	    $this->start();
		
		$this->setChild('TAssetControlMultiple','fk_control');
		$this->setChild('TAssetOFControl','fk_control');
		
	}	
}

class TAssetControlMultiple extends TObjetStd
{
	function __construct() 
	{
		$this->set_table(MAIN_DB_PREFIX.'asset_control_multiple');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_control','type=entier;index;');
		$this->add_champs('value','type=chaine;');
		
	    $this->start();
		
	}
	
	function visu_select_control(&$db, &$form, $name)
	{
		$sql = 'SELECT rowid, libelle FROM '.MAIN_DB_PREFIX.'asset_control WHERE type = "checkboxmultiple"';
		$resql = $db->Execute($sql);
		
		$res = '<select name="'.$name.'"><option value=""></option>';
		
		while($db->Get_line())
		{
			$fk_control = $db->Get_field('rowid');
			$res.= '<option '.($this->fk_control == $fk_control ? 'selected="selected"' : '').' value="'.$fk_control.'">'.$db->Get_field('libelle').'</option>';
		}
		
		$res.= '</select>';
		
		return $res;
	}
	
}

class TAssetOFControl extends TObjetStd
{
	function __construct() 
	{
		$this->set_table(MAIN_DB_PREFIX.'assetOf_control');
    	$this->TChamps = array(); 	  
		$this->add_champs('fk_assetOf,fk_control','type=entier;');
		$this->add_champs('response','type=chaine;');
		
	    $this->start();		
	}
	
}
