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
            ,'NEEDOFFER'=>'En attente de prix fournisseur'
            ,'ONORDER'=>'En attente de produit(s) en commande'
            ,'VALID'=>'Valide pour production'
            ,'OPEN'=>'En cours de production'
			,'CLOSE'=>'Terminé'
		);
		
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'assetOf');
	  
		$this->add_champs('entity,fk_user,fk_assetOf_parent,fk_soc,fk_commande,fk_project','type=entier;index;');
		$this->add_champs('entity,temps_estime_fabrication,temps_reel_fabrication,mo_cost,mo_estimated_cost,compo_cost,compo_estimated_cost,total_cost,total_estimated_cost','type=float;');
		$this->add_champs('ordre,numero,status','type=chaine;');
		$this->add_champs('date_besoin,date_lancement','type=date;');
		$this->add_champs('note','type=text;');
		$this->_init_vars();
		$this->start();
		
		$this->TWorkstation=array();
		$this->status='DRAFT';
		
		$this->setChild('TAssetOFLine','fk_assetOf');
		$this->setChild('TAssetWorkstationOF','fk_assetOf');
		$this->setChild('TAssetOF','fk_assetOf_parent');
		$this->setChild('TAssetOFControl','fk_assetOf');
		
		$this->date_besoin = time();
		$this->date_lancement = 0;
		
		//Tableau d'erreurs
		$this->errors = array();
		
		$this->current_cost_for_to_make = 0; // montant utilisé pour les entrées de stock
	}
	
	function set_current_cost_for_to_make() {
		$qty = 0;	
			
		foreach($this->TAssetOFLine as &$line) {
            if($line->type=='TO_MAKE') $qty+=$line->qty_used;
        }
		
		if($qty>0) $this->current_cost_for_to_make = $this->total_cost / $qty;
		
	}
	
	function load(&$db, $id/*, $loadOFChild=true*/) {
		global $conf;
		
		$res = parent::load($db,$id,true);
		
	    	$this->set_temps_fabrication(true);
		$this->set_fourniture_cost();
		$this->set_current_cost_for_to_make();
		
	        foreach($this->TAssetOFLine as &$line) {
        		 $line->of_numero = $this->numero;
			$line->current_cost_for_to_make = $this->current_cost_for_to_make;
        	}
			
	        foreach($this->TAssetWorkstationOF as &$ws) {
        	    $ws->of_status = $this->status;
	            $ws->of_fk_project = $this->fk_project;
        	}
        
		return $res;
	}
	
	function validate(&$PDOdb) {
		
		global $conf;
		
		$TIDOFToValidate = array($this->rowid);
		
		if($conf->global->ASSET_CHILD_OF_STATUS_FOLLOW_PARENT_STATUS) $this->getListeOFEnfants($PDOdb, $TIDOFToValidate, $this->rowid);
		
		foreach ($TIDOFToValidate as $id_of) {
			
			$of = new TAssetOF;
			$of->load($PDOdb, $id_of);
			
			// On valide pas une of qui est déjà validé ou supérieur
			if($of->rowid <= 0 || $of->status != 'DRAFT') continue;

			$of->status = 'VALID';
			
			if($this->rowid == $id_of) { // Ca c'est juste pour l'of sur lequel on se trouve.
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						$of->TAssetOFLine[$k]->set_values($row);
					//	if (empty($of->TAssetOFLine[$k]->qty)) $of->TAssetOFLine[$k]->qty = $of->TAssetOFLine[$k]->qty_needed;
					}
				}
			}
			$of->createOfAndCommandesFourn($PDOdb);
			$of->unsetChildDeleted = true;
			
			$of->save($PDOdb);
			
		}
		
	}
	
	function create_new_project() {
		
		global $db, $user;
		
		dol_include_once('/projet/class/project.class.php');
		
		// On crée un projet
		$project = new Project($db);
		
		$project->ref = TAssetWorkstationOF::get_next_ref_project();
	
		// On récupère le fk_commande associé
		if($_REQUEST['action'] === 'createOFCommande') $fk_commande = $_REQUEST['fk_commande'];
		else $fk_commande = $this->fk_commande;
		
		if(!empty($fk_commande)) {
			
			dol_include_once('/commande/class/commande.class.php');
			$commande = new Commande($db);
			$commande->fetch($fk_commande);
			
			// On nomme le projet avec la ref de la commande d'origine
			if(!empty($commande->ref)) $project->title.= 'Commande client '.$commande->ref;
			$this->fk_project = $project->create($user);
			
			// On associe la commande au projet
			$project->update_element('commande', $fk_commande);
			
		}
		
	}
	
	function set_fourniture_cost() {
		
		$this->compo_cost = 0;
		$this->compo_estimated_cost = 0;
		
		foreach($this->TAssetOFLine as &$line) {
			//TODO il manque ici les coefficients de frais généraux. A récupérer depuis la nomenclature lors de la création de l'OF
			$this->compo_cost+= $line->qty_used * $line->pmp;
			$this->compo_estimated_cost+= $line->qty_needed * $line->pmp;
		}
		
	}
	
	static function addStockMouvementDolibarr($fk_product,$qty,$description, $fk_entrepot,$price = 0)
	{
		global $db, $user,$conf;

		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$mouvS = new MouvementStock($db);
		
		$conf->global->PRODUIT_SOUSPRODUITS = false; // Dans le cas asset il ne faut pas de destocke recurssif
		
		if($fk_entrepot > 0)
		{			
				if($qty > 0) {
					$result=$mouvS->reception($user, $fk_product, $fk_entrepot, $qty, $price, $description);
				} else {
					$result=$mouvS->livraison($user,$fk_product, $fk_entrepot, -$qty, $price, $description);
					
				}
		}
	}
	
	function set_temps_fabrication($justPrice=false) {
		global $db, $user, $conf;
        dol_include_once('/projet/class/task.class.php');
			
		$this->temps_estime_fabrication=0;
		$this->temps_reel_fabrication=0;
		$this->mo_cost = $this->mo_estimated_cost = 0;
		
		$night = $this->isNight();
		
		foreach($this->TAssetWorkstationOF as &$ws) {
			
			$this->temps_estime_fabrication+=$ws->nb_hour;
			$this->temps_reel_fabrication+=$ws->nb_hour_real;
			
			if ($night) $thm = $ws->ws->thm_night;
			else $thm = $ws->ws->thm;
			
			$this->mo_cost+= $ws->nb_hour_real * ($thm + $ws->ws->thm_machine);
			$this->mo_estimated_cost+= $ws->nb_hour * ($thm + $ws->ws->thm_machine);
			
            if(!$justPrice && $ws->fk_project_task>0) {
               
               $task = new Task($db); 
               $task->fetch($ws->fk_project_task);
               if($task->date_start<$this->date_lancement) {
                   $task->date_start = $this->date_lancement;
                   $task->update($user);
               }
               
            }
			
		}
		
	}
	
	function isNight()
	{
		global $conf;
		
		$night = false;
		if (!empty($conf->global->WORKSTATION_TRANCHE_HORAIRE_THM_NUIT)) 
		{
			// Cas simple
			$tranche = explode('-', $conf->global->WORKSTATION_TRANCHE_HORAIRE_THM_NUIT);
			$heure_de_saisie = date('Hi');
			
			if ($heure_de_saisie >= $tranche[0] || $heure_de_saisie <= $tranche[1]) $night = true;
		}
		
		return $night;
	}
	
	function getNomUrl($picto=0) {
		return '<a href="'.dol_buildpath('/of/fiche_of.php?id='.$this->getId().'"', 2).'>'
				.($picto ? img_picto('','object_list.png','',0).' ' : '')
				.$this->numero
				.'</a>';
	}
	
	function setDelaiLancement($time = 0) {
		
		if((empty($this->date_lancement) && $this->status != 'DRAFT')
		 || ( $this->date_lancement < $time ))
		 {
			
			$nb_day_prod = 0;
			$nb_day_service = 0;
			
			foreach ($this->TAssetOFLine as $k => &$ofLine)
			{
				//Methode 1, le MAX(appro) + SUM(service)
				if($ofLine->type == 'NEEDED') {
					$nb = $ofLine->getNbDayForReapro(); // si besoin de stock
					if($ofLine->product->type == 1) {
						$nb_day_service+=$nb;
					}
					else {
						if($nb_day_prod<$nb)$nb_day_prod = $nb;
					}
					
				}
			}	
			
			$delai = $nb_day_prod + $nb_day_service;
			$this->date_lancement = strtotime('+'.$delai.' day midnight');
			
			if( $this->date_lancement < $time ) $this->date_lancement = $time;
			
			$this->setDelaiLancementForParent();
		}
	}
	
	function setDelaiLancementForParent() {
//var_dump($this->fk_assetOf_parent);exit;
//		return false;
		if($this->fk_assetOf_parent>0) {
			
			$PDOdb=new TPDOdb;
			
			$of=new TAssetOF;
			if($of->load($PDOdb, $this->fk_assetOf_parent)>0) {
				$of->setDelaiLancement($this->date_lancement);
				$of->save($PDOdb);
			}
			
			
			$PDOdb->close();
			
			
		}
		
		
	}
	
	function save(&$PDOdb) {
		global $user,$langs,$conf, $db;

		$this->setDelaiLancement();

		$this->set_temps_fabrication();
		$this->set_fourniture_cost();
		$this->total_cost = $this->compo_cost + $this->mo_cost;
		$this->total_estimated_cost = $this->compo_estimated_cost + $this->mo_estimated_cost;
		
		
		$this->entity = $conf->entity;

		if(!empty($conf->global->USE_LOT_IN_OF))
		{
			$this->setLotWithParent($PDOdb);
		}
		
		//Sécurité sur la maj de l'objet, si on supprime les lignes d'un OF en mode edit, lors de l'enregistrement les infos sont ré-insert avec un fk_product à 0
		foreach ($this->TAssetOFLine as $k => &$ofLine)
		{
			if (!$ofLine->fk_product)
			{
				unset($this->TAssetOFLine[$k]);
			}
		}
		
		$this->destockOrStockPartialQty($PDOdb, $this);
		
		if($this->fk_project == 0 && $conf->global->ASSET_AUTO_CREATE_PROJECT_ON_OF) $this->create_new_project();
		
		foreach($this->TAssetOF as &$of) $of->fk_project = $this->fk_project;
		
		parent::save($PDOdb);

        $this->getNumero($PDOdb, true);
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_SAVE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
	}
	
    function getNumero(&$PDOdb, $save=false) {
        global $db;
    
        if(empty($this->numero)) {
            dol_include_once('core/lib/functions2.lib.php');

            $mask = 'OF{00000}';
            $numero = get_next_value($db,$mask,'assetOf','numero');
           
            if($save) {
                $this->numero = $numero;
                
                $wc = $this->withChild;
                $this->withChild=false;
                parent::save($PDOdb);
                $this->withChild=$wc;
                
            }
            
        }
        else{
            $numero = $this->numero;
        }

        return $numero;
        
    }
    
	function setLotWithParent(&$PDOdb)
	{
		if (count($this->TAssetOFLine) && $this->fk_assetOf_parent)
		{
			$ofParent = new TAssetOF;
			$ofParent->load($PDOdb, $this->fk_assetOf_parent);
			
			foreach($ofParent->TAssetOFLine as $ofLigneParent)
			{
				foreach($this->TAssetOFLine as $ofLigne)
				{
					if($ofLigne->fk_product == $ofLigneParent->fk_product)
					{
						if (empty($this->update_parent))
						{
							$ofLigne->lot_number = $ofLigneParent->lot_number;
							$ofLigne->save($PDOdb);	
						}
						else 
						{
							$ofLigneParent->lot_number = $ofLigne->lot_number;
							$ofLigneParent->save($PDOdb);
						}
					}
				}
			}
		}
	}
	
	//Associe les équipements à l'OF
	function setEquipement(&$PDOdb)
	{
		//pre($this->TAssetOFLine,true);exit;
		foreach($this->TAssetOFLine as $TAssetOFLine)
		{
			$TAssetOFLine->setAsset($PDOdb,$this,true);	
		}
		
		return true;
	}
	
	function delLine(&$PDOdb,$iline)
	{
		global $user,$langs,$conf,$db;
		
		$this->TAssetOFLine[$iline]->to_delete=true;
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_DEL_LINE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
	}
	
	//Ajout d'un produit TO_MAKE à l'OF
	function addProductComposition(&$PDOdb, $fk_product, $quantite_to_make=1, $fk_assetOf_line_parent=0, $fk_nomenclature=0)
	{
		global $conf;
		
		$Tab = $this->getProductComposition($PDOdb,$fk_product, $quantite_to_make, $fk_nomenclature, $fk_assetOf_line_parent);
		foreach($Tab as $fk_product => $TProd) 
		{
		    foreach ($TProd as $prod)
			{
				$this->addLine($PDOdb, $prod->fk_product, 'NEEDED', $prod->qty,$fk_assetOf_line_parent, '', 0, 0, $prod->note_private );
			}
		}
		
		return true;
	}
	
	//Retourne les produits NEEDED de l'OF concernant le produit $id_produit
	function getProductComposition(&$PDOdb,$id_product, $quantite_to_make, $fk_nomenclature=0, $fk_assetOf_line_parent=0)
	{
		global $db,$conf;
		
		$Tab=array();

		if ($conf->nomenclature->enabled)
		{
			dol_include_once('/nomenclature/class/nomenclature.class.php');
			
			//$TNomen = TNomenclature::get($PDOdb, $id_product);
			if ($fk_nomenclature)
			{
				$TNomen = new TNomenclature;
				$TNomen->load($PDOdb, $fk_nomenclature);
				
				if (!empty($TNomen))
				{
					
					$TRes = $TNomen->getDetails($quantite_to_make);
					
					$this->getProductComposition_arrayMerge($PDOdb, $Tab, $TRes, 1, true, $fk_assetOf_line_parent);
				}
			}
			
			
			
		}
		else 
		{
			include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
			$product = new Product($db);
			$product->fetch($id_product);
			$TRes = $product->getChildsArbo($product->id);
			// var_dump($TRes);
			$this->getProductComposition_arrayMerge($PDOdb,$Tab, $TRes, $quantite_to_make);
		}
		
		return $Tab;
	}
	
	private function getProductComposition_arrayMerge(&$PDOdb,&$Tab, $TRes, $qty_parent=1, $createOF=true, $fk_assetOf_line_parent = 0) 
	{
		global $conf;
		//TODO AA c'est de la merde à refaire
		foreach($TRes as $row) 
		{
			$prod = new stdClass;
			$prod->fk_product = $row[0];
			$prod->qty = $row[1] * $qty_parent;
            $prod->note_private = isset($row['note_private']) ? $row['note_private'] : ''; 

			if (!empty($conf->global->ASSETOF_NOT_CONCAT_QTY_FOR_NEEDED))
			{
				$Tab[$prod->fk_product][]=$prod;
			}
			else
			{
				if(isset($Tab[$prod->fk_product])) 
				{
					$Tab[$prod->fk_product][0]->qty += $prod->qty;
				}
				else 
				{
					$Tab[$prod->fk_product][]=$prod;	
				}
			}
			
			if (!empty($conf->global->CREATE_CHILDREN_OF))
			{
				if(!empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT) && !empty($row['childs'])) 
				{
					if(!$createOF) {
						$this->getProductComposition_arrayMerge($PDOdb, $Tab, $row['childs'], $prod->qty);
					}
				}
				
				if ((!empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT) && !empty($row['childs'])) || empty($conf->global->CREATE_CHILDREN_OF_COMPOSANT))
				{
					if($createOF) {
						$this->createOFifneeded($PDOdb, $prod->fk_product, $prod->qty, $fk_assetOf_line_parent);
					}
				}
				
			}
			
		}
		
	} 
	
	/*
	 * Crée une OF si produit composé pas en stock
	 */
	function createOFifneeded(&$PDOdb,$fk_product, $qty_needed, $fk_assetOfLine_parent = 0) {
		global $conf,$db;

		$reste = TAssetOF::getProductStock($fk_product)-$qty_needed;


		if($reste>=0) {
			return null;
		}
		else {
			$k=$this->addChild($PDOdb,'TAssetOF');
			$this->TAssetOF[$k]->status = "DRAFT";
			$this->TAssetOF[$k]->fk_project = $this->fk_project;
			$this->TAssetOF[$k]->fk_soc = $this->fk_soc;
			$this->TAssetOF[$k]->date_besoin = dol_now();
			$this->TAssetOF[$k]->addLine($PDOdb, $fk_product, 'TO_MAKE', abs($qty_needed), $fk_assetOfLine_parent);
			
			return $k;
		}
	}
	
	static function getProductNeededQty($fk_product, $include_draft_of=true, $include_of_from_order = false, $date='', $type='NEEDED') {
		
		global $db;
		
		$sql = "SELECT SUM( IF(qty_needed>0,qty_needed - qty_stock, qty-qty_stock) ) as qty 
				FROM ".MAIN_DB_PREFIX."assetOf_line l 
					LEFT JOIN ".MAIN_DB_PREFIX."assetOf of ON(l.fk_assetOf = of.rowid)
			WHERE l.fk_product=".$fk_product."
			AND type='".$type."' AND of.status IN (".($include_draft_of ? "'DRAFT',": '')."'VALID','OPEN')	
			";
		if(!empty($date))$sql.=" AND of.date_besoin<='".$date."'";
		if(!$include_of_from_order) $sql.=" AND of.fk_commande = 0 ";
		
		$res = $db->query($sql);
		
		$obj = $db->fetch_object($res);
		
		return (float)$obj->qty;
		
		
	}
	
	/*
	 * retourne le stock restant du produit
	 */
	static function getProductStock($fk_product, $fk_warehouse=0, $include_draft_of=true) {
	//TODO finish ! or not
		global $db;
		dol_include_once('/product/class/product.class.php');
		
		$product = new Product($db);
		$product->fetch($fk_product);
		$product->load_stock();
		
        if($fk_warehouse>0)$stock = $product->stock_warehouse[$fk_warehouse]->real;
        else $stock =$product->stock_reel;
        
      /*  $of_qty = self::getProductNeededQty($fk_product, $include_draft_of);
        
		$stock-= $of_qty;
*/
		return $stock;
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
	function addLine(&$PDOdb, $fk_product, $type, $quantite=1,$fk_assetOf_line_parent=0, $lot_number='',$fk_nomenclature=0,$fk_commandedet=0, $note_private = '')
	{
		global $user,$langs,$conf,$db;
		
		$k = $this->addChild($PDOdb, 'TAssetOFLine');
		
		$TAssetOFLine = &$this->TAssetOFLine[$k];
		
		$TAssetOFLine->fk_assetOf_line_parent = $fk_assetOf_line_parent;
		$TAssetOFLine->entity = $user->entity;
		$TAssetOFLine->fk_product = $fk_product;
		$TAssetOFLine->fk_asset = 0;
		$TAssetOFLine->type = $type;
		$TAssetOFLine->qty_needed = $quantite;
		$TAssetOFLine->qty = (!empty($conf->global->ASSET_ADD_NEEDED_QTY_ZERO) && $type === 'NEEDED') ? 0 : $quantite;
		$TAssetOFLine->qty_used = (!empty($conf->global->ASSET_ADD_NEEDED_QTY_ZERO) && $type === 'NEEDED' || $type === 'TO_MAKE') ? 0 : $quantite;
		$TAssetOFLine->note_private = $note_private;
		
        	$TAssetOFLine->fk_product_fournisseur_price = -2;
        
		if ($conf->nomenclature->enabled && !$fk_nomenclature)
		{
			dol_include_once('/nomenclature/class/nomenclature.class.php');
			
			$TNomen = array();
			
			if($fk_commandedet > 0) {
				$TNomen = TNomenclature::get($PDOdb,  $fk_commandedet, false, 'commande');
			}
			
			if(empty($TNomen)) $TNomen = TNomenclature::get($PDOdb,  $fk_product);
			if(count($TNomen) == 1) {
				$TAssetOFLine->fk_nomenclature = $TNomen[0]->getId();
				$fk_nomenclature = $TAssetOFLine->fk_nomenclature ;
			}
		}
		else{
			$TAssetOFLine->fk_nomenclature = $fk_nomenclature;
		}
		
		
		$TAssetOFLine->lot_number = $lot_number;
		
        	$TAssetOFLine->initConditionnement($PDOdb);
		
		if($fk_nomenclature>0) {
			$TAssetOFLine->nomenclature_valide = true;
		}
		
		$idAssetOFLine = $TAssetOFLine->save($PDOdb);
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_ADD_LINE',$TAssetOFLine,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
        
		if($type=='TO_MAKE') 
		{
			$this->addWorkstation($PDOdb, $fk_product,$fk_nomenclature,$quantite);
			$this->addProductComposition($PDOdb,$fk_product, $quantite,$idAssetOFLine,$fk_nomenclature);
		}
		
		// Pour ajouter directement les stations de travail, attachées au produit grâce à l'onglet "station de travail" disponible dans la fiche produit
		if(!empty($conf->workstation->enabled) && $type == "TO_MAKE") 
		{
			//$sql = "SELECT fk_asset_workstation, nb_hour"; 
			$sql = "SELECT fk_workstation as fk_asset_workstation, nb_hour";
			//$sql.= " FROM ".MAIN_DB_PREFIX."asset_workstation_product";
			$sql.= " FROM ".MAIN_DB_PREFIX."workstation_product";
			$sql.= " WHERE fk_product = ".$fk_product;
			$resql = $db->query($sql);
			
			if($resql) 
			{
				while($res = $db->fetch_object($resql)) 
				{
					$this->addofworkstation($PDOdb, $res->fk_asset_workstation, $res->nb_hour);
				}
	
			}
			
			$this->save($PDOdb);
			$this->load($PDOdb, $this->getId());
		}
	}
	
	function addofworkstation(&$PDOdb, $fk_asset_workstation, $nb_hour=0) 
	{
		global $conf;
		 
		$coef = 1;
		if (!empty($conf->global->ASSET_COEF_WS)) $coef = $conf->global->ASSET_COEF_WS;
		
		$k = $this->addChild($PDOdb, 'TAssetWorkstationOF');
		
		$this->TAssetWorkstationOF[$k]->fk_asset_workstation = $fk_asset_workstation;
		$this->TAssetWorkstationOF[$k]->nb_hour = $nb_hour * $coef;
	}
	
	function updateLines(&$PDOdb,$TQty)
	{
		foreach($this->TAssetOFLine as $TAssetOFLine)
		{
			$TAssetOFLine->qty_used = $TQty[$TAssetOFLine->getId()];
			$TAssetOFLine->save($PDOdb);
		}
	}
	
	/* 
	 * Fonction qui permet de mettre à jour les postes de travail liais à un produit
	 * pour la création d'un OF depuis une fiche produit
	 */
	function addWorkStation(&$PDOdb, $fk_product, $fk_nomenclature = 0, $qty_needed = 1) 
	{
		global $conf;
		
		if (!empty($conf->workstation->enabled))
		{
			if($conf->nomenclature->enabled) {
				
				if($fk_nomenclature>0) {
					dol_include_once('/nomenclature/class/nomenclature.class.php');
					
					$n=new TNomenclature;
					if($n->load($PDOdb, $fk_nomenclature, true)) {
						
						foreach($n->TNomenclatureWorkstation as &$nws) {
							
							if(($nws->nb_hour_manufacture > 0 || $nws->nb_hour_prepare > 0) || $conf->global->ASSET_AUTHORIZE_ADD_WORKSTATION_TIME_0_ON_OF) {
							
								$k = $this->addChild($PDOdb, 'TAssetWorkstationOF');
								$this->TAssetWorkstationOF[$k]->fk_asset_workstation = $nws->fk_workstation;
								$this->TAssetWorkstationOF[$k]->nb_hour_prepare = $nws->nb_hour_prepare; 
								$this->TAssetWorkstationOF[$k]->nb_hour_manufacture = $nws->nb_hour_manufacture*$qty_needed;
								$this->TAssetWorkstationOF[$k]->nb_hour = $this->TAssetWorkstationOF[$k]->nb_hour_prepare + $this->TAssetWorkstationOF[$k]->nb_hour_manufacture ;
								
								$this->TAssetWorkstationOF[$k]->rang = $nws->rang;
								
								$this->TAssetWorkstationOF[$k]->nb_hour_real = 0;
                                				$this->TAssetWorkstationOF[$k]->note_private = $nws->note_private;
								$this->TAssetWorkstationOF[$k]->ws = $nws->workstation;
							
							}
							
						}
						
					}
					
				}
				
			}
			else {
				$sql = "SELECT fk_workstation as fk_asset_workstation, nb_hour";
				$sql.= " FROM ".MAIN_DB_PREFIX."workstation_product";
				$sql.= " WHERE fk_product = ".$fk_product;
				$PDOdb->Execute($sql);
				
				while($res = $PDOdb->Get_line()) 
				{
					$ws = new TAssetWorkstation;
					$ws->load($PDOdb, $res->fk_asset_workstation);
					$k = $this->addChild($PDOdb, 'TAssetWorkstationOF');
					$this->TAssetWorkstationOF[$k]->fk_asset_workstation = $res->fk_asset_workstation;
					$this->TAssetWorkstationOF[$k]->nb_hour = $res->nb_hour;
					$this->TAssetWorkstationOF[$k]->nb_hour_real = 0;
					$this->TAssetWorkstationOF[$k]->ws = $ws;
				}
				
			}
		
		}
		
	}
	
    function launchOF(&$PDOdb) 
    {
        global $conf;
      
        $qtyIsValid = $this->checkQtyAsset($PDOdb, $conf);
		
        if ($qtyIsValid)
        {
            $this->status = 'OPEN';
            $this->setEquipement($PDOdb); 
            $this->save($PDOdb); 
            
            return true;
        }
                
        return false;
    }
    
	//Finalise un OF => incrémention/décrémentation du stock
	function closeOF(&$PDOdb)
	{
	    global $langs, $conf, $db, $user;
        
		dol_include_once('/projet/class/task.class.php');
		
		$TIDOFToValidate = array($this->rowid);
		if($conf->global->ASSET_CHILD_OF_STATUS_FOLLOW_PARENT_STATUS) $this->getListeOFEnfants($PDOdb, $TIDOFToValidate, $this->rowid);
		
		foreach ($TIDOFToValidate as $id_of) 
		{
			$of = new TAssetOF;
			$of->load($PDOdb, $id_of);
			
			// On passe pas un of en prod s'il l'est déjà ou s'il n'est pas au statut validé
			if($of->rowid <= 0 || $of->status != 'OPEN') continue;
			
		    $of->status = "CLOSE";
	        
			include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			
	        if (empty($conf->global->OF_ALLOW_FINISH_OF_WITH_UNRECEIVE_ORDER) && !$of->checkCommandeFournisseur($PDOdb))
	        {
                setEventMessage($langs->trans('OFAssetCmdFournNotFinish'), 'errors');
                return false;
	        }
			
			foreach($of->TAssetOFLine as &$AssetOFLine)
			{
				if($AssetOFLine->type == "TO_MAKE")
				{
					 if($AssetOFLine->qty_used == 0) $AssetOFLine->qty_used = $AssetOFLine->qty;
					
					 $AssetOFLine->stockQtyToMakeAsset($PDOdb, $of); 
				} 
				else 
				{
					$qty_needed = !empty($AssetOFLine->qty_needed) ? $AssetOFLine->qty_needed : $AssetOFLine->qty;
					if($AssetOFLine->qty_used == 0) $AssetOFLine->qty_used = $qty_needed;
					
					$AssetOFLine->destockQtyUsedAsset($PDOdb);
				}
			}
		
			
			foreach($of->TAssetWorkstationOF as &$wsof) {
				
				if($wsof->fk_project_task > 0) {
					
					$t=new Task($db);
					$t->fetch($wsof->fk_project_task);
					if($t->progress<100) {
						$t->progress = 100;
						$t->update($user);
					}
					
				}
				
				if($wsof->nb_hour_real == 0) {
					$wsof->nb_hour_real = $wsof->nb_hour;
				}
				
			}
		
			$of->save($PDOdb);

		}

        return true;
	}
	
	
	/*
	 * Permet le lancer l'OF pour la production et de faire le destockage des NEEDED
	 */
	function openOF(&$PDOdb)
	{
		global $db, $user, $conf;
		
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		dol_include_once("fourn/class/fournisseur.product.class.php");
		dol_include_once("fourn/class/fournisseur.commande.class.php");
		
		$TIDOFToValidate = array($this->rowid);
		
		if($conf->global->ASSET_CHILD_OF_STATUS_FOLLOW_PARENT_STATUS) $this->getListeOFEnfants($PDOdb, $TIDOFToValidate, $this->rowid);
		
		foreach ($TIDOFToValidate as $id_of) {
			
			$of = new TAssetOF;
			$of->load($PDOdb, $id_of);
			
			// On passe pas un of en prod s'il l'est déjà ou s'il n'est pas au statut validé
			if($of->rowid <= 0 || $of->status != 'VALID') continue;
			
	        if($of->launchOF($PDOdb)) 
	        {
				if (!empty($conf->global->OF_USE_DESTOCKAGE_PARTIEL)) {
					
					
					foreach($of->TAssetOFLine as &$AssetOFLine)
					{
						if($AssetOFLine->type == 'TO_MAKE')
						{
							 $AssetOFLine->stockQtyToMakeAsset($PDOdb, $of); 
						} 
						else 
						{
							$AssetOFLine->destockQtyUsedAsset($PDOdb);
						}
					}
					
				}
				else {
					foreach($of->TAssetOFLine as &$AssetOFLine)
					{
						if($AssetOFLine->type=='NEEDED') $AssetOFLine->destockAsset($PDOdb, $AssetOFLine->qty - $AssetOFLine->qty_stock);
					}
				}
	        }
		
		}
		
	}
	
	
	function destockOrStockPartialQty(&$PDOdb, &$of)
	{
		global $conf;
		
		if ($of->status == 'OPEN' || $of->status == 'CLOSE') 
		{
			foreach($of->TAssetOFLine as &$AssetOFLine)
	        {
	            if ($AssetOFLine->type == 'NEEDED') $AssetOFLine->destockQtyUsedAsset($PDOdb);
				else $AssetOFLine->stockQtyToMakeAsset($PDOdb, $of, 1);
			}
			
			return true;
		}
		
		return false;
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
	
	private function addCommandeFourn(&$PDOdb,$ofLigne, $resultatSQL) {

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
		$this->addElementElement($PDOdb,$com,$ofLigne);
	}

	function delete(&$PDOdb)
	{
		global $user,$langs,$conf,$db;
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_DELETE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
		
		parent::delete($PDOdb);
		
		$this->delElementElement($PDOdb);
	}

	function addElementElement(&$PDOdb,&$commandeFourn,&$ofLigne){
		
		$TIdCommandeFourn = $this->getElementElement($PDOdb);

		if(!in_array($commandeFourn->id, $TIdCommandeFourn)){
			$sql = "REPLACE INTO ".MAIN_DB_PREFIX."element_element (fk_source,fk_target,sourcetype,targettype) 
					VALUES (".$ofLigne->fk_assetOf.",".$commandeFourn->id.",'ordre_fabrication','order_supplier')";
			
			$PDOdb->Execute($sql);
		}
		
	}
	
	function delElementElement(&$PDOdb){
		
		$PDOdb->Execute("DELETE FROM ".MAIN_DB_PREFIX."element_element 
						 WHERE sourcetype = 'ordre_fabrication'
						 	AND targettype = 'order_supplier'
						 	AND fk_source = ".$this->getId());
	}
	
	function getElementElement(&$PDOdb){
		
		$TIdCommandeFourn = array();
		
		$sql = "SELECT fk_target 
				FROM ".MAIN_DB_PREFIX."element_element 
				WHERE fk_source = ".$this->getId()." 
					AND sourcetype = 'ordre_fabrication' 
					AND targettype = 'order_supplier'";
		
		$PDOdb->Execute($sql);
		
		while($PDOdb->Get_line()){
			$TIdCommandeFourn[] = $PDOdb->Get_field('fk_target');
		}
		
		return $TIdCommandeFourn;

	}
	
	function createOfAndCommandesFourn(&$PDOdb) {
		global $db, $user;
		
		dol_include_once("fourn/class/fournisseur.commande.class.php");
		
		$TabOF = array();
		$TabOF[] = $this->rowid;
		$this->getListeOFEnfants($PDOdb, $TabOF);
		
		// Boucle pour chaque OF de l'arbre
		foreach($TabOF as $idOf){
			
			// On charge l'OF
			$assetOF = new TAssetOF;
			$assetOF->load($PDOdb, $idOf);
			
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
								
								$this->addCommandeFourn($PDOdb,$ofLigne, $res);

							} 
							else { // Suffisemment de stock, donc destockage :
								$assetOF->openOF($PDOdb);
							}
						}
						elseif(!$res->compose_fourni) { //Commande Fournisseur
						
							$this->addCommandeFourn($PDOdb,$ofLigne, $res);

							// On récupère les OF enfants pour les supprimer
							$TabIdEnfantsDirects = $assetOF->getEnfantsDirects();

							foreach($TabIdEnfantsDirects as $idOF) {
							
								$assetOF->removeChild("TAssetOF", $idOF);
							}
							
							//Suppression des lignes NEEDED puisque inutiles
							$assetOF->delLineNeeded($PDOdb);
							$assetOF->unsetChildDeleted = true;
							
							$assetOF->save($PDOdb);
							
							// On casse la boucle
							break;

						}

					} 
					else { // Fournisseur interne (Bourguignon) [PH - 14/04/15 - FIXME c'est pas que pour bourguignon ?]
					
						if($ofLigne->fk_product_fournisseur_price == -1) { // Sortie de stock, kill OF enfants
							
							$TabIdEnfantsDirects = $assetOF->getEnfantsDirects();
							
							foreach($TabIdEnfantsDirects as $idOF) {
							
								$assetOF->removeChild("TAssetOF", $idOF);
							}

							$assetOF->save($PDOdb);
							
							// On casse la boucle
							break;

						}
						elseif($ofLigne->fk_product_fournisseur_price == -2){ // Fabrication interne
							//[PH] FIXME - pourquoi on destock maintenant ? On valide tt juste l'OF 
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
								//$assetOF->openOF($PDOdb);
								$assetOF->status = 'VALID';
							}
													
						}
						
					}
					
				}

			}
			
		}

	}
	
	function delLineNeeded(&$PDOdb){
		
		foreach($this->TAssetOFLine as $k=>$ofLigne){

			if($ofLigne->type == "NEEDED"){
				$this->delLine($PDOdb, $k);
			}
		}
		
	}
	
	static function ordre($ordre='ASAP'){
		
		
		return TAssetOF::$TOrdre[$ordre];
	}
	
	
	function getListeOFEnfants(&$PDOdb, &$Tid, $id_parent=null, $recursive = true) {
			
		if(is_null($id_parent))$id_parent = $this->getId();
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf";
		$sql.= " WHERE fk_assetOf_parent = ".$id_parent;
		$sql.= " ORDER BY fk_assetOf_parent, rowid";
		
		$Tab = $PDOdb->ExecuteAsArray($sql);
		foreach($Tab as $row) {
			$Tid[] = $row->rowid;
			if ($recursive) $this->getListeOFEnfants($PDOdb, $Tid, $row->rowid);
		}
				
	}
	
	function getOFEnfantWithProductToMake(&$PDOdb, &$res, $fk_product, $level=0, $recursive = true)
	{
		global $db;
		
		$tab = array();
				
		$sql = "SELECT a.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."assetOf a";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."assetOf_line al ON (a.rowid = al.fk_assetOf AND al.type = 'TO_MAKE' AND al.fk_product = ".(int) $fk_product.")";
		$sql.= " WHERE fk_assetOf_parent = ".$this->getId();
		
		$tab = $PDOdb->ExecuteAsArray($sql);
		
		foreach ($tab as $val)
		{
			if ($recursive)
			{
				$TAssetOF = new TAssetOF;
				$TAssetOF->load($PDOdb, $val->rowid);
				foreach ($TAssetOF->TAssetOFLine as $line)
				{
					$TAssetOF->getOFEnfantWithProductToMake($PDOdb, $res, $line->fk_product, $level+1);
				}
			}

			$res[] = array('id_assetOf' => $val->rowid, 'level' => $level);
		}
		
	}
	
	function getLineProductToMake() {
		
		if(!empty($this->TAssetOFLine)) {
			foreach ($this->TAssetOFLine as $line) {
				if($line->type === 'TO_MAKE') return $line;
			}
		}
		
		return 0;
		
	}
	
	/*
	 * Permet de supprimer le/les OF enfants
	 * return 0 si aucun OF
	 * return array id_assetOf si un ou +sieurs OF
	 */
	function deleteOFEnfant(&$PDOdb, $fk_product)
	{
		$res = $tab = array();
		$this->getOFEnfantWithProductToMake($PDOdb, $tab, $fk_product);
		
		if (count($tab) <= 0) return 0;

		foreach ($tab as $row)
		{
			if ($row['level'] == 0)
			{
				$TAssetOF = new TAssetOF;
				$TAssetOF->load($PDOdb, $row['id_assetOf']);
				$TAssetOF->delete($PDOdb);
			}
			$res[] = $row['id_assetOf'];
		}

		return $res;
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
		global $langs,$db;
		
		$fill = true;
		foreach ($this->TAssetOFLine as $OFLine)
		{
			if (empty($OFLine->lot_number)) 
			{
				$product = new Product($db);
				$product->fetch($OFLine->fk_product);
				$this->errors[] = $langs->trans('OFAssetLotEmpty', $product->label, $product->getNomUrl());
				$fill = false;
			}
		}
			
		return $fill;
	}
	
	function checkCommandeFournisseur(&$PDOdb)
	{
		global $db;
		
		$res = true;
		$Tid = $this->getElementElement($PDOdb);
		
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
	 * return true if OK else false NULL
	 */
	function checkQtyAsset(&$PDOdb, &$conf)
	{
		global $db;
		
        if(!$conf->global->USE_LOT_IN_OF) return true;
         
		$qtyIsValid = true;
		foreach($this->TAssetOFLine as $TAssetOFLine)
		{
			$qtyIsValid &= $TAssetOFLine->setAsset($PDOdb,$this, false);
		}
		
		return $qtyIsValid;
	}

	function updateControl(&$PDOdb, $subAction)
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
			
			$this->save($PDOdb);
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
					$ofControl->delete($PDOdb);
					continue;
				}
				
				//Toutes les valeurs sont envoyées sous forme de tableau
				$val = !empty($TResponse[$ofControl->getId()]) ? implode(',', $TResponse[$ofControl->getId()]) : '';
				$ofControl->response = $val;
				$ofControl->save($PDOdb);
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
				$PDOdb = new TPDOdb;
				$values = explode(',', $value);
				$control = new TAssetControl;
				$control->load($PDOdb, $fk_control);
				
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

	function getControlPDF(&$PDOdb)
	{
		$res = array();
		
		foreach ($this->TAssetOFControl as $ofControl)
		{
			$control = new TAssetControl;
			$control->load($PDOdb, $ofControl->fk_control);
			
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
		$this->add_champs('entity,fk_assetOf,fk_product,fk_product_fournisseur_price,fk_entrepot,fk_nomenclature,nomenclature_valide',array('type'=>'integer','index'=>true));
		$this->add_champs('qty_needed,qty,qty_used,qty_stock,conditionnement,conditionnement_unit,pmp',array('type'=>'float'));
		$this->add_champs('type,lot_number,measuring_units',array('type'=>'string'));
        $this->add_champs('note_private',array('type'=>'text'));

		//clé étrangère
		parent::add_champs('fk_assetOf_line_parent',array('type'=>'integer','index'=>true));
		
		$this->TType=array('NEEDED','TO_MAKE');
		
		$this->errors = array();
		$this->TFournisseurPrice=array();
		$this->TWorkstation=array();
		$this->_init_vars();
	    $this->start();
		$this->setChild('TAssetOFLine','fk_assetOf_line_parent');
		
		$this->product = null;
	}
	
	//$qty_to_re_stock est normalement tjr positif
	//TODO remove useless
	function reStockAsset(&$PDOdb, $qty_to_re_stock)
	{
		global $conf;

		if ($qty_to_re_stock == 0) return false;

		$TAsset = $this->getAssetLinked($PDOdb);
        foreach($TAsset as $asset) 
        {
        	//Si la contenance max de mon équipement peut récupérer la qty restante
        	if (($asset->contenance_value - $asset->contenancereel_value) < $qty_to_re_stock){
				$qty_to_re_stock = 0;
			}
			
			$labelMvt = 'Suppression Ordre de Fabrication';
			if($this->type == 'TO_MAKE') $labelMvt = 'Suppression Ordre de Fabrication';
		
         	$asset->save($PDOdb,$user
	            ,$labelMvt.' n°'.$OF->numero.' - Equipement : '.$asset->serial_number
	            ,$qty_to_re_stock, false, $this->fk_product, false, $fk_entrepot);
        }
	}
	
	function stockProduct($qty_to_stock) {
		
		return $this->destockProduct(-$qty_to_stock) ;
	}
	
	function destockProduct($qty_to_destock) {
		global $conf;
		
		$sens = ($qty_to_destock>0) ? -1 : 1;
		$qty_to_destock_rest =  abs($qty_to_destock);
		
		$labelMvt = 'Utilisation via Ordre de Fabrication';
		if($this->type == 'TO_MAKE') $sens == 1 ? $labelMvt = 'Création via Ordre de Fabrication' : $labelMvt = 'Suppression via Ordre de Fabrication';
		
		if($this->type == 'TO_MAKE') $fk_entrepot = !empty($conf->global->ASSET_MANUAL_WAREHOUSE) ? $this->fk_entrepot : $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE;
		else $fk_entrepot = !empty($conf->global->ASSET_MANUAL_WAREHOUSE) ? $this->fk_entrepot : $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED;
		
		/*$asset=new TAsset; //TODO if asset not implemented
		$asset->addStockMouvementDolibarr($this->fk_product, $sens * $qty_to_destock_rest,$labelMvt.' n°'.$this->of_numero, false, 0, $fk_entrepot);
		*/
		
		$price = 0;
		
		if($this->type=='TO_MAKE') {
			$price = $this->current_cost_for_to_make;
		}
		
		TAssetOF::addStockMouvementDolibarr($this->fk_product, $sens * $qty_to_destock_rest, $labelMvt,$fk_entrepot, $price);
		
		$this->update_qty_stock($sens * $qty_to_destock_rest);

	}
	
	function stockAsset(&$PDOdb, $qty_to_stock, $add_only_qty_to_contenancereel=false) {
		
		return $this->destockAsset($PDOdb, -$qty_to_stock, $add_only_qty_to_contenancereel);
		
	}
	
	/**
	 * @param 	$qty_to_destock		if < 0 = stockage, if > 0 = destockage
	 */
    function destockAsset(&$PDOdb, $qty_to_destock, $add_only_qty_to_contenancereel=false) 
    {
        global $conf;

        if($qty_to_destock==0) return false; // on attend une qty ! A noter que cela peut-être négatif en cas de sous conso il faut restocker un bout 
        
        $sens = ($qty_to_destock>0) ? -1 : 1;
        $qty_to_destock_rest =  abs($qty_to_destock);

		if($this->type == 'TO_MAKE') $fk_entrepot = !empty($conf->global->ASSET_MANUAL_WAREHOUSE) ? $this->fk_entrepot : $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE;
		else $fk_entrepot = !empty($conf->global->ASSET_MANUAL_WAREHOUSE) ? $this->fk_entrepot : $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED;

		//echo $sens." x ".$qty_to_destock_rest.'<br>';
		
		$labelMvt = 'Utilisation via Ordre de Fabrication';
		if($this->type == 'TO_MAKE') $sens == 1 ? $labelMvt = 'Création via Ordre de Fabrication' : $labelMvt = 'Suppression via Ordre de Fabrication';
		
        if(!$conf->global->USE_LOT_IN_OF || empty($conf->asset->enabled)) 
        {
			$this->stockProduct($sens * $qty_to_destock_rest);
        }
		else 
		{
			$TAsset = $this->getAssetLinked($PDOdb);

            if(empty($TAsset)) {
				$asset=new TAsset;

				//On a pas d'asset lié à la ligne, cependant si on a un lot il faut quand même destocker un équipement de ce lot
				if($this->lot_number){
					$assetOf = new TAssetOF;
					$assetOf->load($PDOdb, $this->fk_assetOf);
					$this->setAsset($PDOdb, $assetOf);
					
					echo $this->lot_number;exit;
				}
				else{ //Sinon effectivement on destocke juste le produit sans les équipements
					$this->stockProduct( $sens * $qty_to_destock_rest);
				}
                
            }
            else{
            	
                foreach($TAsset as $asset) 
                {
                     $qty_asset_to_destock = ($conf->global->ASSET_NEGATIVE_DESTOCK) ? $qty_to_destock_rest : $asset->contenancereel_value;
                     
                     if($qty_to_destock_rest - $qty_asset_to_destock <= 0) 
                     {
                         $qty_asset_to_destock = $qty_to_destock_rest;
					 }

					 //echo $sens." x ".$qty_asset_to_destock.'<br>';
					 $this->update_qty_stock($sens * $qty_asset_to_destock);
					 
                     $asset->save($PDOdb,$user
                             ,$labelMvt.' n°'.$this->of_numero.' - Equipement : '.$asset->serial_number
                             ,$sens * $qty_asset_to_destock, false, $this->fk_product, false, $fk_entrepot, $add_only_qty_to_contenancereel);
                    
                    $qty_to_destock_rest-= $qty_asset_to_destock;
                    
                    if($qty_to_destock_rest<=0)break;
                }
				
            }
			
		}
		
        return $this->save($PDOdb);
    }
    
	// Met à jour la ##### de quantité stock, si tu comprends pas demande à PH
	function update_qty_stock($qty) {
	
		if($this->type=='TO_MAKE') $this->qty_stock += $qty; // Je destock la quantité prise unitairement dans l'équipement
		else $this->qty_stock -= $qty; // Je destock la quantité prise unitairement dans l'équipement
					 
	}
	
	/*
	 * Donne le délai en jour avant réapprovisionnement
	 */
	function getNbDayForReapro() {
		global $db, $user, $conf;	
		
		if($conf->supplierorderfromorder->enabled && $this->type=='NEEDED') {
			
			$stock_needed = TAssetOF::getProductStock($this->fk_product);
			if($stock_needed > 0) return 0;
			
			dol_include_once('/supplierorderfromorder/class/sofo.class.php');
			
			$nb = TSOFO::getMinAvailability($this->fk_product, $this->qty_needed);
		
			return $nb;
		}
		
		return 0;
	}
	
	//Affecte les équipements à la ligne de l'OF
	function setAsset(&$PDOdb,&$AssetOf, $forReal = false)
	{
	  
		global $db, $user, $conf;	
		
        if(!$conf->global->USE_LOT_IN_OF || empty($this->lot_number)) return true;
		
		dol_include_once('/asset/class/asset.class.php');
		
		$completeSql = '';
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'asset';
		
		$is_cumulate = TAsset_type::getIsCumulate($PDOdb, $this->fk_product);
		$is_perishable = TAsset_type::getIsPerishable($PDOdb, $this->fk_product);
		
		//si on cherche à déstocker 5 * 0.10 Kg alors on ne cherche pas un équipement avec + de 5Kg en stock mais bien + de 0.50Kg
		list($qty,$qty_stock) = $this->convertQty();
		
		//echo $this->qty;exit;
		
		//Si type equipement est cumulable alors on destock 1 ou +sieurs équipements jusqu'à avoir la qté nécéssaire
		if ($is_cumulate)
		{
			$sql.= ' WHERE 1 ';
			
			if(!$conf->global->ASSET_NEGATIVE_DESTOCK) $sql.= ' AND contenancereel_value > 0 ';
			
			if ($is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, date_cre ASC, contenancereel_value ASC';
			else $completeSql = ' ORDER BY date_cre ASC, contenancereel_value ASC';
		}
		else 
		{
			$sql.= ' WHERE 1 ';
			
			if(!$conf->global->ASSET_NEGATIVE_DESTOCK) $sql.= ' AND contenancereel_value >= '.($qty - $qty_sotck).' ';// - la quantité déjà utilisé
			
			if ($is_perishable) $completeSql = ' AND DATE_FORMAT(dluo, "%Y-%m-%d") >= DATE_FORMAT(NOW(), "%Y-%m-%d") ORDER BY dluo ASC, contenancereel_value ASC, date_cre ASC LIMIT 1';
			else $completeSql = ' ORDER BY contenancereel_value ASC, date_cre ASC LIMIT 1';
		}

		$sql.= ' AND fk_product = '.$this->fk_product;
		
		if ($conf->global->USE_LOT_IN_OF)
		{
			$sql .= ' AND lot_number = "'.$this->lot_number.'"';
		}
		
		$sql.= $completeSql;
		
		//echo $sql.'<br>';
		
		$Tab = $PDOdb->ExecuteAsArray($sql);

        $no_error = true;
 
		if($this->type == 'NEEDED' && ($AssetOf->status == 'OPEN' || !$forReal )  ) // TODO remove condition status
		{
			if ($qty_stock == $qty) return true; //qty_stock = qté déjà utilisé et qty = qté de besoin, donc si egal alors pas besoin de chercher d'autre équipement
			
			$nbAssetFound = count($Tab);
			$mvmt_stock_already_done = $nbAssetFound > 0 ? true : false;
			$qty_needed = $qty - $qty_stock; // - la quantité déjà utilisé
			
            if ($nbAssetFound == 0 && !$conf->global->ASSET_NEGATIVE_DESTOCK) {
                $AssetOf->errors[] = "La quantité d'équipement pour le produit ID ".$this->fk_product." dans le lot n°".$this->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
                $no_error = false;                 
            }
            else 
            {
            	//On fait un 1er tour pour vérifier la qté
        		$qtyIsEnough = $this->checkAddAssetLink($PDOdb, $Tab, $qty_needed, $forReal, false);
            	   
				if (!$qtyIsEnough && !$conf->global->ASSET_NEGATIVE_DESTOCK) $AssetOf->errors[] = "La quantité d'équipement pour le produit ID ".$this->fk_product." dans le lot n°".$this->lot_number.", est insuffisante pour la conception du ou des produits à créer.";
				else $this->checkAddAssetLink($PDOdb, $Tab, $qty_needed, $forReal);
				
				$no_error = $qtyIsEnough;
            }
			
		}
		
        //TODO on créé un équipement si non trouver, voir pour réintégrer ce comportement sur paramétrage 
		/*
		$this->fk_asset = $idAsset;
		$this->save($PDOdb, $conf);	
*/
        if(!$no_error && !$conf->global->ASSET_NEGATIVE_DESTOCK) return false;
        else return true;
	}
	
	/*
	 *  Converty les quantités en fonction du conditionnement produit
	 */
	function convertQty(){
			
		$conditionnement = $this->conditionnement;
		
		//TODO : mettre tous sur la même unité de mesure
		$qty_stock = $this->qty_stock;
		$qty = $this->qty;
		
		return array($qty, $qty_stock);
	}

	/*
	 * 
	 * Return true si la quantité d'équipement est suffisante
	 */
	function checkAddAssetLink(&$PDOdb, $Tab, $qty_needed, $forReal, $addLink=true)
	{
		global $conf;
		
		if(empty($conf->asset->enabled)) return false;
		
		
		$break = false;
	
		foreach($Tab as $row) 
	   	{
    	 	$asset=new TAsset;
         	$asset->load($PDOdb, $row->rowid);
	        
			//Contient la liste des asset
			$TAsset = $this->getAssetLinked($PDOdb);
			
	         // Si j'ai assez de contenu dans mon équipement
	         if ($asset->contenancereel_value - $qty_needed >= 0)
	         {
	             $qty_to_destock = $qty_needed;
	             $break = true;
	         }
	         else 
	         {
	             // sinon si cumulable
	             $qty_to_destock = $asset->contenancereel_value;
	             $qty_needed -= $asset->contenancereel_value;
	         }
	          
		     if($forReal && $addLink) {
		         $this->addAssetLink($asset);
		    
		         if (!empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) $fk_entrepot = $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED;
		         else $fk_entrepot = $asset->fk_entrepot;
		        
				 //il faut aussi destocker la contenance
				 //$asset->contenancereel_value -= $qty_to_destock;
				
		         $asset->status = 'indisponible';
		         //On affiche aussi l'ID de l'équipement dans la description pcq le serial_number peut être vide
		         //$asset->save($PDOdb,$user,'Utilisation via Ordre de Fabrication n°'.$AssetOf->numero.' - Equipement : '.$asset->getId().' - '.$asset->serial_number, -$qty_to_destock, false, 0, false, $fk_entrepot);
		         $asset->save($PDOdb,$user);
		     }
	    
	         if ($break) break;
		}

		return $break;
	}


    function getAssetLinkedLinks(&$PDOdb, $r='<br />', $sep='<br />') {
        global $conf;
		if(empty($conf->asset->enabled)) return '';
		
        $TAsset = $this->getAssetLinked($PDOdb);
        
        foreach($TAsset as &$asset) {
            
            $r.=$asset->getNomUrl(true,true).$sep;
        }
        
        return $r;
    }
    function getAssetLinked(&$PDOdb, $only_ids=false) {
        global $conf;
		
		if(!empty($conf->asset->enabled)) {
	        $Tab = $TId = array();
			
	        $TId = TAsset::get_element_element($this->getId(), 'TAssetOFLine', 'TAsset');
	        if ($only_ids) return $TId;
			//pre($TId,true);
			
	        foreach($TId as $id) {
	            $asset = new TAsset;
	            if($asset->load($PDOdb, $id)) {
	                $Tab[] = $asset;    
	            }
	        }
			
			return $Tab;
		}
		
        return array();
    }

    function addAssetLink(&$asset) 
    {
    	global $conf;
    	if(!empty($conf->asset->enabled)) {
        	TAsset::set_element_element($this->getId(), 'TAssetOFLine', $asset->getId(), 'TAsset');
        }
    }
	
	function initConditionnement(&$PDOdb) 
	{
		global $conf;
		 
		if(!empty($conf->asset->enabled)) {
			dol_include_once('/asset/class/asset.class.php');
	        	$assetType = new TAsset_type;
	        	$assetType->load_by_fk_product($PDOdb, $this->fk_product);
		        $this->conditionnement = $assetType->getDefaultContenance($this->fk_product);
		        $this->conditionnement_unit = $assetType->contenance_units;
		        $this->measuring_units = $assetType->measuring_units;
			
		}
		else{
			$this->measuring_units = 'unit';
			$this->gestion_stock = 'UNIT';
			$this->conditionnement = 1;
		}	
	}
    
    function libUnite() 
    {
        dol_include_once('/core/lib/product.lib.php');
        return measuring_units_string($this->conditionnement_unit, $this->measuring_units);
    }
    
	//Utilise l'équipement affecté à la ligne de l'OF
	function makeAsset(&$PDOdb, &$AssetOf, $fk_product, $qty_to_make, $idAsset = 0, $lot_number = '')
	{
	   	global $user,$conf;
       
	   	//INFO : si on utilise pas les lots on a pas besoin de créer des équipements => on gère uniquement des mvt de stock
        if(empty($conf->asset->enabled) || empty($conf->global->USE_LOT_IN_OF)) return true;
       	
		if(!dol_include_once('/asset/class/asset.class.php')) return true;

        $assetType = new TAsset_type;
        if($assetType->load_by_fk_product($PDOdb, $fk_product)) 
        {
        	/* On fabrique de la contenance et non pas une quantité de produit au sens strict
        	 * Si on fabrique un produit au sens strict, le type d'équipement de ce produit aura une contenance max à 1, donc ça marche pareil
        	 * En revanche, on a un sac de sable à moitier vide, s'il est réutilisable on va le remplir puis en créer si besoin
        	 * 
			 * A penser :	   [1] [2] [3]
			 * Périsable :		1	0	1
			 * Réutilisable :	0	1	1
			 * 
			 * [1] => on crée de nouveaux équipements
			 * [2] => on réutilise
			 * [3] => si la qté de l'équipement courant = 0 alors on réutilise
			 * 
			 * Dans le process de la validation de la production, les TO_MAKE doivent pré-séléctionner les équipements possibles à la réutilisation
			 * Quand on "Termine" l'OF il faudra prendre la liste des équipements liés pour les remplir puis en créer si nécessaire
			 */
			 
            $contenance_max = $assetType->contenance_value;
            $nb_asset_to_create = ceil($qty_to_make / $contenance_max);
			
			//Qté restante a fabriquer
            $qty_to_make_rest = $qty_to_make;
			
            for($i=0; $i<$nb_asset_to_create; $i++) 
            {
                $TAsset = new TAsset;
                $TAsset->fk_soc = $AssetOf->fk_soc;
                $TAsset->fk_societe_localisation = $conf->global->ASSET_DEFAULT_LOCATION;
                $TAsset->fk_product = $fk_product;
                $TAsset->entity = $conf->entity;
                if(!empty($conf->global->ASSET_DEFAULT_DLUO)) $TAsset->dluo = strtotime(date('Y-m-d').' +'.$conf->global->ASSET_DEFAULT_DLUO.' days');
                else $TAsset->dluo = strtotime(date('Y-m-d'));
				
				//pre($assetType,true);exit;
				
                $TAsset->fk_asset_type = $assetType->getId();
                $TAsset->load_asset_type($PDOdb);
                
                if($qty_to_make_rest>$TAsset->contenance_value) {
                    $qty_to_make_asset = $TAsset->contenance_value;
                }
                else {
                    $qty_to_make_asset = $qty_to_make_rest;
                }
                   
                $qty_to_make_rest-=$qty_to_make_asset;

                $TAsset->contenancereel_value = $qty_to_make_asset;
                $TAsset->lot_number = $lot_number;
				
                if (!empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) $fk_entrepot = $conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE;
                
				if(!$fk_entrepot) exit('ASSET_USE_DEFAULT_WAREHOUSE non définis dans la configuration du module');
                
                $TAsset->fk_entrepot = $fk_entrepot;
				
                $TAsset->save($PDOdb,'','',0,false,0,true,$fk_entrepot); //Save une première fois pour avoir le serial_number + 2ème save pour mvt de stock   
                
                $this->addAssetLink($TAsset);
                
            }

            return true;
        }
        else{
            return false;
        }
		
	}
	
	function getWorkstationsPDF(&$db) //TODO AA c'est quoi ce nom de fonction de merde ?!
	{
		
		$r='';
			
        foreach($this->TWorkstation as &$w) {
            if(!empty($r)) $r.=', ';
            $r.=$w->name;
        }    
            
		return $r;
	}
	
	function load(&$PDOdb, $id) {
		parent::load($PDOdb, $id);
		$this->load_workstations($PDOdb);
		
		$this->loadFournisseurPrice($PDOdb);
		
		$this->load_product();
	}
	
	function load_product() {
		global $db;
		
		if($this->fk_product>0) {
			dol_include_once('/product/class/product.class.php');
			
			$this->product = new Product($db);
			$this->product->fetch($this->fk_product);
			
			$this->pmp = $this->product->pmp;
		}
		
	}
	function delete(&$PDOdb)
	{
		global $user,$langs,$conf,$db;
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_LINE_OF_DELETE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
		
		//La fonction destockAsset en utilisant les lot ne permet pas de remettre la contenancereel_value dans chaque asset utilisés
		($this->type == 'NEEDED') ? $this->stockAsset($PDOdb, $this->qty_stock) : $this->destockAsset($PDOdb, $this->qty_stock);
		
	    // TODO dbdelete()
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
        $PDOdb->Execute($sql);
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "TAssetOFLine" AND targettype = "TAsset"';
        $PDOdb->Execute($sql);

		/* Evite le restockage en double 
         * Si mon OF a 1 TO_MAKE qui a besoin de 2 NEEDED
         * le fait de delete l'OF on va donc delete chacun de ses enfants TAssetOFLine
         * seulement un objet TAssetOFLine de type TO_MAKE a aussi des enfants TAssetOFLine
         */ 
		if ($this->type == 'TO_MAKE') $this->withChild = false;

		parent::delete($PDOdb);
	}
	
	function set_workstations(&$PDOdb, &$TWorkstations)
	{
	    
		if (empty($TWorkstations)) return false;
	
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
		$PDOdb->Execute($sql);
		
        //TODO fonction add_element()
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'element_element (';
		$sql.= 'fk_source, sourcetype, fk_target, targettype';
		$sql.= ') VALUES ';
		
		$save = false;
		foreach ($TWorkstations as $id_workstation) 
		{
			if ($id_workstation <= 0) continue;
			
			$this->TWorkstation[] = $id_workstation;
			$save = true;
			
			$sql.= '(';
			$sql.= (int) $this->rowid.',';
			$sql.= $PDOdb->quote('tassetofline').',';
			$sql.= (int) $id_workstation.',';
			$sql.= $PDOdb->quote('tassetworkstation');
			$sql.= '),';
		}
		
		if ($save)
		{
			$sql = rtrim($sql, ',');
           
			$PDOdb->Execute($sql);
		}
	}
	
	function load_workstations(&$PDOdb)
	{
	    
        $this->TWorkstation=array();
        		
		$sql.= 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= ' WHERE fk_source = '.(int) $this->rowid;
		$sql.= ' AND sourcetype = "tassetofline" AND targettype = "tassetworkstation"';
		
		$Tab = $PDOdb->ExecuteAsArray($sql);
		foreach($Tab as $row) {
		    $w=new TWorkstation;
            $w->load($PDOdb, $row->fk_target);
            
		    $this->TWorkstation[] = $w; 
        }
		
		return $this->TWorkstation;
	}	
	
    // TODO refaire, c'est codé par la mère michèle et je pense que son chat lui a bouffé la cervelle
	function visu_checkbox_workstation(&$db, &$of, &$form, $name)
	{
		$TWSid= array();
		foreach($this->TWorkstation as &$ws) {
		    if(is_object($ws)) $TWSid[] = $ws->getId();
            else  $TWSid[] =(int)$ws;
        }
        
		$sql = 'SELECT name AS libelle, rowid 
		  FROM '.MAIN_DB_PREFIX.'workstation WHERE rowid 
		IN (SELECT fk_asset_workstation FROM '.MAIN_DB_PREFIX.'asset_workstation_of WHERE fk_assetOf = '.(int) $of->rowid.')';
		$resql = $db->query($sql);
		
		$res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($r = $db->fetch_object($resql)) 
		{
		   
			$res .= '<p style="margin:4px 0">'
    			.$form->checkbox1($r->libelle, $name, $r->rowid
    			, (in_array($r->rowid, $TWSid) ? true : false), 'style="vertical-align:text-bottom;"', '', '', 'case_before' 
    			, array('no'=>'', 'yes'=>img_picto('', 'tick.png')) ) 
    			. '</p>';
		}
		
		return $res;
	}
	
	function loadFournisseurPrice(&$PDOdb) {
		$sql = "SELECT  pfp.rowid,  pfp.fk_soc,  pfp.price,  pfp.quantity, pfp.compose_fourni,s.nom as 'name'
		FROM ".MAIN_DB_PREFIX."product_fournisseur_price pfp LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (pfp.fk_soc=s.rowid)
		WHERE fk_product = ".(int)$this->fk_product;

		$PDOdb->Execute($sql);

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
			,$PDOdb->Get_All()
		);

	}
	
	function save(&$PDOdb) 
	{
		global $user,$langs,$conf,$db;
		
		$this->entity = $conf->entity;
		
        if($this->conditionnement==0 && $this->fk_product>0) { //TOCHECK A priori inutile 
            $this->initConditionnement($PDOdb);
        }

		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_LINE_OF_SAVE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
		
		return parent::save($PDOdb);
	}
	
	function getLibelleEntrepot(&$PDOdb, $withStock=true)
	{
		$res = false;
		
		if (!$this->fk_entrepot) return 'Aucun entrepôt séléctionné';
		
		$sql = 'SELECT e.label, "" AS reel FROM '.MAIN_DB_PREFIX.'entrepot e WHERE rowid = '.(int) $this->fk_entrepot;
		if ($withStock) 
		{
			$sql = 'SELECT e.label, ps.reel FROM '.MAIN_DB_PREFIX.'entrepot e
					LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ps ON (ps.fk_entrepot = e.rowid AND ps.fk_product = '.(int) $this->fk_product.')
					WHERE e.rowid = '.(int) $this->fk_entrepot.'';
		} 
		else
		{
			$sql = 'SELECT e.label FROM '.MAIN_DB_PREFIX.'entrepot e WHERE rowid = '.(int) $this->fk_entrepot;
		}
		
		$PDOdb->Execute($sql);
		while ($PDOdb->Get_line())
		{
			$res = $PDOdb->Get_field('label');
			if ($withStock) $res .= ' (Stock: '.$PDOdb->Get_field('reel').')';
		}
		
		if ($res) return $res;
		else return 'Aucun entrepôt séléctionné';
	}
	
	function set_values($row)
	{
		global $conf;
		
		if ($conf->nomenclature->enabled && $this->fk_nomenclature != $row['fk_nomenclature'])
		{
			//
		}
		
		parent::set_values($row);
	}
	
	//Fonction pour le destockage partiel
	function destockQtyUsedAsset(&$PDOdb)
	{
    	// qty_used (saisie via l'interface) - qty_stock (ce qui a déjà été fabriqué)
		$qty_to_destock = $this->qty_used - $this->qty_stock;
		$this->destockAsset($PDOdb, $qty_to_destock); // Tant qu'on utilise l'attribut qty_used via le formulaire on as pas besoin de passer le 4eme param à false
	}
	
	function stockQtyToMakeAsset(&$PDOdb, &$of, $fromCloseOf=0)
	{
		global $conf,$langs;
		
		$qty_make = $this->qty_used - $this->qty_stock;
		
		//TODO si pas d'équipement défini, pas de mouvement de stock ! à corriger
		if ($this->makeAsset($PDOdb, $of, $this->fk_product, $qty_make, 0, $this->lot_number)) $this->stockAsset($PDOdb, $qty_make, true, false); // On stock les nouveaux équipements
		else {
			$this->stockProduct($qty_make);
			setEventMessage($langs->trans('ImpossibleToCreateAsset'), 'errors');
		}
	}
}

/*
 * Link to product
 */
class TAssetWorkstationProduct extends TObjetStd {
	
	function __construct() {
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation_product');
	    	$this->TChamps = array(); 	  
		$this->add_champs('fk_product, fk_asset_workstation','type=entier;index;');
		$this->add_champs('nb_hour_prepare,nb_hour_manufacture,nb_hour,rang','type=float;'); // nombre d'heure associé au poste de charge et au produit
		
		$this->_init_vars();
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
		$this->add_champs('fk_assetOf, fk_asset_workstation, fk_project_task','type=entier;index;');
		$this->add_champs('nb_hour,nb_hour_real,nb_hour_prepare,rang','type=float;'); // nombre d'heure associé au poste de charge sur un OF
		$this->add_champs('note_private',array('type'=>'text'));
		// J'ai rajouté nb_hour_prepare dans cette table parce que quand on veut afficher le nombre d'heures de préparation pour un poste de travail sur l'odt of,
		// celui ci peut être différent ligne par ligne si on a plusieurs fois un même poste de travail sur une nomenclature.
		// as you wish buddy ! AA 
		$this->_init_vars();
	    	$this->start();
		
		$this->ws = new TAssetWorkstation; // TODO replace by TWorkstation
		$this->users = array();
		$this->tasks = array();
	}
	
	function load(&$PDOdb, $id) 
	{	
		parent::load($PDOdb,$id);
		$this->users = $this->get_users($PDOdb);
		$this->tasks = $this->get_tasks($PDOdb);
		
		if($this->fk_asset_workstation >0)
		{
			$this->ws->load($PDOdb, $this->fk_asset_workstation);
            
		}
	}
	
	function createTask(&$PDOdb, &$db, &$conf, &$user, &$OF)
	{
		//l'ajout de poste de travail à un OF en ajax n'initialise pas le $user
		if (!$user->id)	$user->id = GETPOST('user_id');

		$ws = new TAssetWorkstation;
		$ws->load($PDOdb, $this->fk_asset_workstation);
		
		$class_mod = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
		$modTask = new $class_mod;
		
		$projectTask = new Task($db);
		$projectTask->fk_project = $OF->fk_project;
		$projectTask->ref = $modTask->getNextValue(0, $projectTask);
		$projectTask->label = $ws->libelle;
        
        if(!empty($conf->global->ASSET_TASK_HIERARCHIQUE_BY_RANK)) {
            $PDOdb->Execute("SELECT MAX(t.rowid) as rowid 
            FROM ".MAIN_DB_PREFIX."projet_task t LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields tex ON (t.rowid=tex.fk_object)  
            WHERE t.fk_projet=".$this->of_fk_project." AND tex.fk_of=".$this->fk_assetOf);
            $PDOdb->Get_line();
            $projectTask->fk_task_parent = (int)$PDOdb->Get_field('rowid');
            
        }
        else{
            $projectTask->fk_task_parent = 0;    
        }
		
						
		$projectTask->date_start = $OF->date_lancement;
		$projectTask->date_end = $OF->date_besoin;
		$projectTask->planned_workload = $this->nb_hour*3600;
		
        $projectTask->array_options['options_grid_use']=1;
        $projectTask->array_options['options_fk_workstation']=$ws->getId();
		$projectTask->array_options['options_fk_of']=$this->fk_assetOf;
		
		$p = new Product($db);
		$line_product_to_make = $OF->getLineProductToMake();
		if(!empty($line_product_to_make) && ($p->fetch($line_product_to_make->fk_product) > 0)) {
			$projectTask->array_options['options_fk_product']=$p->id;
		}
        
		$res = $projectTask->create($user);
        if($res<0) {
            var_dump($projectTask);
            
            exit('ErrorCreateTaskWS') ;
        }
        else{
            $this->fk_project_task = $projectTask->id;
        }
		
		$this->updateAssociation($PDOdb, $db, $projectTask);
	}
	
	function updateTask(&$PDOdb, &$db, &$conf, &$user, &$OF)
	{
		if (!$user->id)	$user->id = GETPOST('user_id');
		
		$projectTask = new Task($db);
		$projectTask->fetch($this->fk_project_task);
		$projectTask->fk_project = $OF->fk_project;
		
		$projectTask->update($user);
		
		$this->updateAssociation($PDOdb, $db, $projectTask);
	}
	
	function updateAssociation(&$PDOdb, &$db, &$projectTask)
	{
		// Association des utilisateurs aux tâches du projet
		$TUsers = $this->get_users($PDOdb);
		if(!empty($TUsers)) {
			dol_include_once('/projet/class/project.class.php');
			foreach ($TUsers as $id_user_associated_on_task) {
				$p = new Project($db);
				if($p->fetch($projectTask->fk_project) > 0) {
					$p->add_contact($id_user_associated_on_task, 160, 'internal');
					$projectTask->add_contact($id_user_associated_on_task, 180, 'internal');
				}
				
			}
		}
	}
	
	function deleteTask(&$db, &$conf, &$user)
	{
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			
		if (!$user->id)	$user->id = GETPOST('user_id');
		
		$projectTask = new Task($db);
		$projectTask->fetch($this->fk_project_task);
		$projectTask->delete($user);
		
		$this->fk_project_task = 0;
	}
	
	function manageProjectTask(&$PDOdb, &$of)
	{
		global $db,$conf,$user;
		
		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/'.$conf->global->PROJECT_TASK_ADDON.'.php';
		
		$action = '';
		
		if ($of->fk_project > 0 && $this->fk_project_task == 0) $action = 'createTask';
		elseif ($of->fk_project > 0 && $this->fk_project_task > 0) $action = 'updateTask';
		elseif ($of->fk_project == 0 && $this->fk_project_task > 0) $action = 'deleteTask';
		
		switch ($action) 
		{
			case 'createTask':
				$this->createTask($PDOdb, $db, $conf, $user, $of);
				break;
			case 'updateTask':
				$this->updateTask($PDOdb, $db, $conf, $user, $of);
				break;
			case 'deleteTask':
				$this->deleteTask($db, $conf, $user);
				break;
			default:
				break;
		}
	}
	
	function save(&$PDOdb)
	{
	 	global $conf;
		
        if (!empty($conf->global->ASSET_USE_PROJECT_TASK))
		{
			$of=new TAssetOF;
        	$of->load($PDOdb, $this->fk_assetOf);
			if ($of->status === 'VALID') $this->manageProjectTask($PDOdb, $of);
		}
		
		parent::save($PDOdb);
	}

	static function get_next_ref_project() {
		
		global $conf;
		
		// Récupération de la référence suivante en fonction du masque (std dolibarr)
	    $defaultref='';
	    $modele = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;
	    // Search template files
	    $file=''; $classname=''; $filefound=0;
	    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	    foreach($dirmodels as $reldir)
	    {
	    	$file=dol_buildpath($reldir."core/modules/project/".$modele.'.php',0);
	    	if (file_exists($file))
	    	{
	    		$filefound=1;
	    		$classname = $modele;
	    		break;
	    	}
	    }
	    if ($filefound)
	    {
		    $result=dol_include_once($reldir."core/modules/project/".$modele.'.php');
		    $modProject = new $classname;
	
		    $defaultref = $modProject->getNextValue($thirdparty,$object);
	    }
		
		return $defaultref;
		
	}

	function set_values($Tab)
	{
		global $db,$user;
		
		if ($this->fk_project_task)
		{
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			
			$projectTask = new Task($db);
			$projectTask->fetch($this->fk_project_task);
			
			if (isset($Tab['nb_hour']))
			{
				$projectTask->planned_workload = Tools::string2num($Tab['nb_hour'])*3600;
			}
			
			if (isset($Tab['progress']))
			{
				$projectTask->progress = $Tab['progress'];
				unset($Tab['progress']);
			}
			
			$projectTask->update($user);
		}
		
		parent::set_values($Tab);
	}
	
	function delete(&$PDOdb)
	{
		global $db,$user,$conf;
		
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetworkstationof" AND (targettype = "user" OR targettype = "task")';
		$PDOdb->Execute($sql);
		
		if ($this->fk_project_task > 0)
		{
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/'.$conf->global->PROJECT_TASK_ADDON.'.php';
			
			if (!$user->id) $user->id = GETPOST('user_id');
			
			$projectTask = new Task($db);
			$projectTask->fetch($this->fk_project_task);
			$projectTask->delete($user);
		}
		
		parent::delete($PDOdb);
	}
	
	function set_users(&$PDOdb, $Tusers)
	{
		if (empty($Tusers)) return false;
		
		$this->users = array();
		
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetworkstationof" AND targettype = "user"';
		$PDOdb->Execute($sql);
		
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
			$sql.= $PDOdb->quote('tassetworkstationof').',';
			$sql.= (int) $id_user.',';
			$sql.= $PDOdb->quote('user');
			$sql.= '),';
		}
		
		if ($save)
		{
			$sql = rtrim($sql, ',');
			$PDOdb->Execute($sql);
		}
	}
	
	function get_users(&$PDOdb)
	{		
		$res = array();
		
		$sql.= 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= ' WHERE fk_source = '.(int) $this->rowid;
		$sql.= ' AND sourcetype = "tassetworkstationof" AND targettype = "user"';
		
		$PDOdb->Execute($sql);
		while ($PDOdb->Get_line()) $res[] = $PDOdb->Get_field('fk_target');
		
		return $res;
	}

	function getUsersPDF(&$PDOdb)
	{
		$res = '';
		if(count($this->users) <= 0) return '-';
		
		$sql = 'SELECT lastname, firstname FROM '.MAIN_DB_PREFIX.'user WHERE rowid IN ('.implode(',', $this->users).')';
		$PDOdb->Execute($sql);
		
		while ($PDOdb->Get_line()) 
		{
			$res .= $PDOdb->Get_Field('lastname').' '.$PDOdb->Get_field('Firstname').', ';
		}
		
		$res = rtrim($res, ', ');
		
		return $res;
	}
	
	//Mode opératoire et non tâche projet
	function set_tasks($PDOdb, $Ttask)
	{
		if (empty($Ttask)) return false;
		
		$this->tasks = array();
		
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element WHERE fk_source = '.(int) $this->rowid.' AND sourcetype = "tassetworkstationof" AND targettype = "task"';
		$PDOdb->Execute($sql);
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'element_element (';
		$sql.= 'fk_source, sourcetype, fk_target, targettype';
		$sql.= ') VALUES ';
		
		$save = false;
		foreach ($Ttask as $id_task) 
		{
			if ($id_task <= 0) continue;
				
			$this->tasks[] = $id_task;
			$save = true;
			
			$sql.= '(';
			$sql.= (int) $this->rowid.',';
			$sql.= $PDOdb->quote('tassetworkstationof').',';
			$sql.= (int) $id_task.',';
			$sql.= $PDOdb->quote('task');
			$sql.= '),';
		}
		
		if ($save)
		{
			$sql = rtrim($sql, ',');
			$PDOdb->Execute($sql);
		}
	}
	
	function get_tasks(&$PDOdb)
	{		
		$res = array();
		
		$sql.= 'SELECT fk_target FROM '.MAIN_DB_PREFIX.'element_element';
		$sql.= ' WHERE fk_source = '.(int) $this->rowid;
		$sql.= ' AND sourcetype = "tassetworkstationof" AND targettype = "task"';
		
		$PDOdb->Execute($sql);
		while ($PDOdb->Get_line()) $res[] = $PDOdb->Get_field('fk_target');
		
		return $res;
	}


	function getTasksPDF(&$PDOdb)
	{
		$res = '';
		if(count($this->tasks) <= 0) return '-';
		
		$sql = 'SELECT libelle FROM '.MAIN_DB_PREFIX.'asset_workstation_task WHERE rowid IN ('.implode(',', $this->tasks).')';
		$PDOdb->Execute($sql);
		
		while ($PDOdb->Get_line()) 
		{
			$res .= $PDOdb->Get_Field('libelle').', ';
		}
		
		$res = rtrim($res, ', ');
		
		return $res;
	}
	
	function set_project_task(&$PDOdb, $progress)
	{
		global $db;
		
		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		
		$projectTask = new Task($db);
		$projectTask->fetch($this->fk_project_task);
		
		$projectTask->progress = $progress;
		$projectTask->update($db);
	}
	
}



dol_include_once('/workstation/class/workstation.class.php');

if (class_exists('TWorkstation')) {
	class TAssetWorkstation extends TWorkstation {
	//TODO remove it and use workstation object
		function __construct() {
	    	
	    	parent::__construct();
		    $this->start();
			
		}
		
		function load(&$PDOdb, $id)
		{
			parent::load($PDOdb, $id);
			$this->libelle = $this->name;
		}
		
		function save(&$PDOdb) {
			global $conf;
			
			$this->name = $this->libelle;
			$this->entity = $conf->entity;
			
			parent::save($PDOdb);
		}
		
	}
}

class TAssetWorkstationTask extends TObjetStd
{
	function __construct()
	{
		$this->set_table(MAIN_DB_PREFIX.'asset_workstation_task');
		$this->TChamps = array(); 	  
		$this->add_champs('fk_workstation','type=entier;index;');
		$this->add_champs('libelle','type=chaine;');
		$this->add_champs('description','type=text;');
		
	    $this->start();
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
		
		$this->errors = array();
		
	    $this->start();		
	}
	
	function save(&$PDOdb)
	{
		global $user,$langs,$conf,$db;
		
		parent::save($PDOdb);
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_CONTROL_SAVE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
	}
	
	function delete(&$PDOdb)
	{
		global $user,$langs,$conf,$db;
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		$interface = new Interfaces($db);
		$result = $interface->run_triggers('ASSET_OF_CONTROL_DELETE',$this,$user,$langs,$conf);
		if ($result < 0)
		{
			$this->errors[] = $interface->errors;
		}
		
		parent::delete($PDOdb);
	}
	
}
