<?php

require('config.php');
dol_include_once('/asset/class/asset.class.php');
dol_include_once('/asset/class/ordre_fabrication_asset.class.php');
dol_include_once('/asset/lib/asset.lib.php');
dol_include_once('/core/lib/ajax.lib.php');
dol_include_once('/core/lib/product.lib.php');
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/fourn/class/fournisseur.commande.class.php');
dol_include_once('/product/class/html.formproduct.class.php');
dol_include_once('/core/lib/date.lib.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/nomenclature/class/nomenclature.class.php');

if(!$user->rights->asset->all->lire) accessforbidden();
if(!$user->rights->asset->of->write) accessforbidden();

// Load traductions files requiredby by page
$langs->load("other");
$langs->load("orders");
$langs->load("asset@asset");

$hookmanager->initHooks(array('ofcard'));

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	global $user, $db, $conf, $langs;	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;
	
	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	$action=__get('action','view');
	
	switch($action) {
		case 'new':
		case 'add':
			$assetOf=new TAssetOF;
			$assetOf->set_values($_REQUEST);

			$fk_product = __get('fk_product',0,'int');
			$fk_nomenclature = __get('fk_nomenclature',0,'int');

			_fiche($PDOdb, $assetOf,'edit', $fk_product, $fk_nomenclature);

			break;

		case 'edit':
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id']);

			_fiche($PDOdb,$assetOf,'edit');
			break;
		
		case 'create':
		case 'save':
			$assetOf=new TAssetOF;
			
			if(!empty($_REQUEST['id'])) {
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				$mode = 'view';
			}
			else {
				$mode = $action == 'create' ? 'view' : 'edit';
			}

			$assetOf->set_values($_REQUEST);
			
			$fk_product = __get('fk_product_to_add',0);
			$quantity_to_create = __get('quantity_to_create',1);
			$fk_nomenclature = __get('fk_nomenclature',0);
			if($fk_product > 0) {
				$assetOf->addLine($PDOdb, $fk_product, 'TO_MAKE',$quantity_to_create,0,'',$fk_nomenclature);	
			}

			if(!empty($_REQUEST['TAssetOFLine'])) 
			{
				foreach($_REQUEST['TAssetOFLine'] as $k=>$row) 
				{
				    if(!isset( $assetOf->TAssetOFLine[$k] ))  $assetOf->TAssetOFLine[$k] = new TAssetOFLine;
					
					if (!empty($conf->global->ASSET_DEFINED_WORKSTATION_BY_NEEDED))
					{
						$assetOf->TAssetOFLine[$k]->set_workstations($PDOdb, $row['fk_workstation']);
						unset($row['fk_workstation']);
					}

					$assetOf->TAssetOFLine[$k]->set_values($row);
				}

				foreach($assetOf->TAssetOFLine as &$line) {
					$line->TAssetOFLine=array();
				}
			}

			if(!empty($_REQUEST['TAssetWorkstationOF'])) {
				foreach($_REQUEST['TAssetWorkstationOF'] as $k=>$row) 
				{
					//Association des utilisateurs à un poste de travail
					if (!empty($conf->global->ASSET_DEFINED_USER_BY_WORKSTATION))
					{
						$assetOf->TAssetWorkstationOF[$k]->set_users($PDOdb, $row['fk_user']);
						unset($row['fk_user']);
					}
					
					//Association des opérations à une poste de travail (mode opératoire)
					if (!empty($conf->global->ASSET_DEFINED_OPERATION_BY_WORKSTATION))
					{
						$assetOf->TAssetWorkstationOF[$k]->set_tasks($PDOdb, $row['fk_task']);
						unset($row['fk_task']);
					}
					
					$assetOf->TAssetWorkstationOF[$k]->set_values($row);
				}
			}

			
			$assetOf->entity = $conf->entity;

			//Permet de mettre à jour le lot de l'OF parent
			if (!empty($assetOf->fk_assetOf_parent)) $assetOf->update_parent = true;
			$assetOf->save($PDOdb);
			
			_fiche($PDOdb,$assetOf, $mode);

			break;

		case 'valider':
			$assetOf=new TAssetOF;
            $id = GETPOST('id');
            if(empty($id)) exit('Where is Waldo ?');
            
			$assetOf->load($PDOdb, $id);
			
           //Si use_lot alors check de la saisie du lot pour chaque ligne avant validation
			if (!empty($conf->global->USE_LOT_IN_OF) && !empty($conf->global->OF_LOT_MANDATORY)) {
				if (!$assetOf->checkLotIsFill())
				{
					_fiche($PDOdb,$assetOf, 'view');
					break;
				}
			}
			
			$assetOf->validate($PDOdb);
			
			//Relaod de l'objet OF parce que createOfAndCommandesFourn() fait tellement de truc que c'est le bordel
			$assetOf=new TAssetOF;
			if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
			
			_fiche($PDOdb,$assetOf, 'view');

			break;

		case 'lancer':
			$assetOf=new TAssetOF;
            $id = GETPOST('id');
            if(empty($id)) exit('Where is Waldo ?');
            
			$assetOf->load($PDOdb,$id);
            
			$assetOf->openOF($PDOdb);
            
			$assetOf->load($PDOdb,$id);
			_fiche($PDOdb, $assetOf, 'view');

			break;

		case 'terminer':
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id']);
			$assetOf->closeOF($PDOdb);
			$assetOf->load($PDOdb, $_REQUEST['id']);
            
			_fiche($PDOdb,$assetOf, 'view');
			
			break;

		case 'delete':
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id']);
			
			//$PDOdb->db->debug=true;
			$assetOf->delete($PDOdb);
			
			
			header('Location: '.dol_buildpath('/asset/liste_of.php?delete_ok=1',1));
			exit;
			
			break;

		case 'view':
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id']);
			_fiche($PDOdb, $assetOf, 'view');

			break;
		case 'createDocOF':
			
			$id_of = $_REQUEST['id'];
			
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $id_of, false);
			
			$TOFToGenerate = array($assetOf->rowid);
			
			if($conf->global->ASSET_CONCAT_PDF) $assetOf->getListeOFEnfants($PDOdb, $TOFToGenerate, $assetOf->rowid);
			
			foreach($TOFToGenerate as $id_of) {
			
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $id_of, false);
				$TRes[] = generateODTOF($PDOdb, $assetOf);
				
			}
			
			$TFilePath = get_tab_file_path($TRes);
			//var_dump($TFilePath);exit;
			if($conf->global->ASSET_CONCAT_PDF) {
			
				$pdf=pdf_getInstance();
				if (class_exists('TCPDF'))
				{
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($langs));
				
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
				//$pdf->SetCompression(false);
				                 
				$pagecount = concat($pdf, $TFilePath);
				
				if ($pagecount)
				{
					$pdf->Output($TFilePath[0],'F');
					if (! empty($conf->global->MAIN_UMASK))
					{
						@chmod($file, octdec($conf->global->MAIN_UMASK));
					}
				}
				
			}
			
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=asset&entity=1&file=".$TRes[0]['dir_name']."/".$TRes[0]['num_of'].".pdf");
			
			break;
			
		case 'control':
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id']);
		
			$subAction = __get('subAction', false);
			if ($subAction) $assetOf->updateControl($PDOdb, $subAction);
			
			_fiche_control($PDOdb, $assetOf);
		
			break;
		
		case 'addAssetLink':
			$assetOf=new TAssetOF;
            $assetOf->load($PDOdb, __get('id', 0, 'int'));
            
			$idLine = __get('idLine', 0, 'int');
			$idAsset = __get('idAsset', 0, 'int');
			
			if ($idLine && $idAsset)
			{
				$find = false;
				foreach ($assetOf->TAssetOFLine as $TAssetOFLine)
				{
					if ($TAssetOFLine->getId() == $idLine)
					{
						$find = true;
						
						$asset = new TAsset;
						$asset->load($PDOdb, $idAsset);
						$TAssetOFLine->addAssetLink($asset);
						break;
					}
				}
				
				if (!$find) setEventMessage('Erreur sur l\'identifiant de l\'équipement', 'errors');
			}
			else 
			{
				setEventMessage('Erreur sur la saisie de l\'équipement.', 'errors');	
			}
			
           _fiche($PDOdb, $assetOf, 'edit'); 
		   
			break;
		
        case 'deleteAssetLink':
            $assetOf=new TAssetOF;
            $assetOf->load($PDOdb, __get('id', 0, 'int'));
            
			$idLine = __get('idLine', 0, 'int');
			$idAsset = __get('idAsset', 0, 'int');
			
			if ($idLine && $idAsset)
			{
				TAsset::del_element_element($PDOdb, $idLine, $idAsset, 'TAsset');
			}
			else
			{
				setEventMessage('Erreur sur un des identifiants', 'errors');
			}
			
           _fiche($PDOdb, $assetOf, 'edit'); 
        
           break;    
            
		default:
			$assetOf=new TAssetOF;
			$assetOf->load($PDOdb, $_REQUEST['id'], false);
			
			_fiche($PDOdb, $assetOf, 'view');
			
			break;
	}
	
}



function generateODTOF(&$PDOdb, &$assetOf) {
	
	global $db,$conf;

	foreach($assetOf as $k => $v) {
		print $k."<br />";
	}
	
	$TBS=new TTemplateTBS();
	dol_include_once("/product/class/product.class.php");

	$TToMake = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit à fabriquer
	$TNeeded = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit nécessaires
	$TWorkstations = array(); // Tableau envoyé à la fonction render contenant les informations concernant les stations de travail
	$TWorkstationUser = array(); // Tableau de liaison entre les postes et les utilisateurs
	$TWorkstationTask = array(); // Tableau de liaison entre les postes et les tâches 'mode opératoire'
	$TAssetWorkstation = array(); // Tableau de liaison entre les composants et les postes de travails
	$TControl = array(); // Tableau de liaison entre l'OF et les controles associés
	
	$societe = new Societe($db);
	$societe->fetch($assetOf->fk_soc);
	
	//pre($societe,true); exit;
	
	if (!empty($conf->global->ASSET_USE_CONTROL))
	{
		$TControl = $assetOf->getControlPDF($PDOdb);
	}
	
	// On charge les tableaux de produits à fabriquer, et celui des produits nécessaires
	foreach($assetOf->TAssetOFLine as $k=>&$v) {

		$prod = new Product($db);
		$prod->fetch($v->fk_product);
		$prod->fetch_optionals($prod->id);
		
		if($conf->nomenclature->enabled){
				
				$n = new TNomenclature;
				
				if(!empty($v->fk_nomenclature)) {
					$n->load($PDOdb, $v->fk_nomenclature);
					$TTypesProductsNomenclature = $n->getArrayTypesProducts();
				}
				
		}
		
		$qty = !empty($v->qty_needed) ? $v->qty_needed : $v->qty; 
		
		if($v->type == "TO_MAKE") {
			
			$TToMake[] = array(
				'type' => $v->type
				, 'qte' => $qty
				, 'nomProd' => $prod->ref
				, 'designation' => utf8_decode($prod->label)
				, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
				, 'lot_number' => $v->lot_number ? "\n(Lot numero ".$v->lot_number.")" : ""
				, 'code_suivi_ponderal' => $prod->array_options['options_suivi_ponderal'] ? "\n".$prod->array_options['options_suivi_ponderal'] : "\n(Aucun)"
			);

		}
		else if($v->type == "NEEDED") {
	
			$unitLabel = "";

			if($prod->weight_units == 0) {
				$unitLabel = "Kg";
			} else if ($prod->weight_units == -3) {
				$unitLabel = "g";
			} else if ($prod->weight_units == -6) {
				$unitLabel = "mg";
			} else if ($prod->weight_units == 99) {
				$unitLabel = "livre(s)";
			}
								
			$TNeeded[] = array(
				'type' => $conf->nomenclature->enabled ? $TTypesProductsNomenclature[$v->fk_product] : $v->type
				, 'qte' => $qty
				, 'nomProd' => $prod->ref
				, 'designation' => utf8_decode($prod->label)
				, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
				, 'poids' => $prod->weight
				, 'unitPoids' => $unitLabel
				, 'finished' => $prod->finished?"PM":"MP"
				, 'lot_number' => $v->lot_number ? "\n(Lot numero ".$v->lot_number.")" : ""
				, 'code_suivi_ponderal' => $prod->array_options['options_suivi_ponderal'] ? "\n(Code suivi ponderal : ".$prod->array_options['options_suivi_ponderal'].")" : ""
			);
			
			if (!empty($conf->global->ASSET_DEFINED_WORKSTATION_BY_NEEDED))
			{
				$TAssetWorkstation[] = array(
					'nomProd'=>utf8_decode($prod->label)
					,'workstations'=>utf8_decode($v->getWorkstationsPDF($db))
				);
			}
			
		}

	}

	// On charge le tableau d'infos sur les stations de travail de l'OF courant
	foreach($assetOf->TAssetWorkstationOF as $k => $v) {
		
		$TWorkstations[] = array(
			'libelle' => utf8_decode($v->ws->libelle)
			//,'nb_hour_max' => utf8_decode($v->ws->nb_hour_max)
			,'nb_hour_max' => utf8_decode($v->ws->nb_hour_capacity)
			,'nb_hour_real' => utf8_decode($v->nb_hour_real)
			,'nb_hour_preparation' => utf8_decode($v->nb_hour_prepare)
			,'nb_heures_prevues' => utf8_decode($v->nb_hour)
		);
		
		if (!empty($conf->global->ASSET_DEFINED_USER_BY_WORKSTATION))
		{
			$TWorkstationUser[] = array(
				'workstation'=>utf8_decode($v->ws->libelle)
				,'users'=>utf8_decode($v->getUsersPDF($PDOdb))
			);
		}
		
		if (!empty($conf->global->ASSET_DEFINED_OPERATION_BY_WORKSTATION))
		{
			$TWorkstationTask[] = array(
				'workstation'=>utf8_decode($v->ws->libelle)
				,'tasks'=>utf8_decode($v->getTasksPDF($PDOdb))
			);
		}

	}
	
	$dirName = 'OF'.$assetOf->rowid.'('.date("d_m_Y").')';
	$dir = DOL_DATA_ROOT.'/asset/'.$dirName.'/';
	
	@mkdir($dir, 0777, true);
	
	if(defined('TEMPLATE_OF')){
		$template = TEMPLATE_OF;
	}
	else{
		$template = "templateOF.odt";
		//$template = "templateOF.doc";
	}
	
	$refcmd = '';
	if(!empty($assetOf->fk_commande)) {
		$cmd = new Commande($db);
		$cmd->fetch($assetOf->fk_commande);
		$refcmd = $cmd->ref;
	}
	
	$barcode_pic = getBarCodePicture($assetOf);
	
	$file_path = $TBS->render(dol_buildpath('/asset/exempleTemplate/'.$template)
		,array(
			'lignesToMake'=>$TToMake
			,'lignesNeeded'=>$TNeeded
			,'lignesWorkstation'=>$TWorkstations
			,'lignesAssetWorkstations'=>$TAssetWorkstation
			,'lignesUser'=>$TWorkstationUser
			,'lignesTask'=>$TWorkstationTask
			,'lignesControl'=>$TControl
		)
		,array(
			'date'=>date("d/m/Y")
			,'numeroOF'=>$assetOf->numero
			,'statutOF'=>utf8_decode(TAssetOF::$TStatus[$assetOf->status])
			,'prioriteOF'=>utf8_decode(TAssetOF::$TOrdre[$assetOf->ordre])
			,'date'=>date("d/m/Y")
			,'refcmd'=>$refcmd
			,'societe'=>$societe->name
			,'logo'=>DOL_DATA_ROOT."/mycompany/logos/".MAIN_INFO_SOCIETE_LOGO
			,'barcode'=>$barcode_pic
			,'use_lot'=>(int) $conf->global->ASSET_DEFINED_WORKSTATION_BY_NEEDED
			,'defined_user'=>(int) $conf->global->ASSET_DEFINED_USER_BY_WORKSTATION
			,'defined_task'=>(int) $conf->global->ASSET_DEFINED_OPERATION_BY_WORKSTATION
			,'use_control'=>(int) $conf->global->ASSET_USE_CONTROL
		)
		,array()
		,array(
			'outFile'=>$dir.$assetOf->numero.".odt"
			,"convertToPDF"=>true
			//'outFile'=>$dir.$assetOf->numero.".doc"
		)
		
	);
	
	return array('file_path'=>$file_path, 'dir_name'=>$dirName, 'num_of'=>$assetOf->numero);
	
	header("Location: ".DOL_URL_ROOT."/document.php?modulepart=asset&entity=1&file=".$dirName."/".$assetOf->numero.".pdf");
	//header("Location: ".DOL_URL_ROOT."/document.php?modulepart=asset&entity=1&file=".$dirName."/".$assetOf->numero.".doc");

}

function getBarCodePicture(&$assetOf) {
	
	$code = $assetOf->numero;
	include_once './script/get_barcode_pic.php';
	return $tmpfname;
	
}

function get_tab_file_path($TRes) {
	
	$tab = array();
	
	foreach($TRes as $TData) {
		$tab[] = strtr($TData['file_path'], array('.odt'=>'.pdf'));
	}
	
	return $tab;
	
}

function _fiche_ligne(&$form, &$of, $type){
	global $db, $conf, $langs;

	$formProduct = new FormProduct($db);

    $PDOdb=new TPDOdb;
	$TRes = array();
	foreach($of->TAssetOFLine as $k=>&$TAssetOFLine){
		$product = &$TAssetOFLine->product;
        if(is_null($product)) {
            $product=new Product($db);
            $product->fetch($TAssetOFLine->fk_product);
        }

		$conditionnement = $TAssetOFLine->conditionnement;
		$conditionnement_unit = $TAssetOFLine->libUnite(); 
		
		if($TAssetOFLine->measuring_units!='unit' && !empty($TAssetOFLine->measuring_units)) {
            $conditionnement_label = ' x '.$conditionnement.$conditionnement_unit;
            $conditionnement_label_edit = ' par '.$form->texte('', 'TAssetOFLine['.$k.'][conditionnement]', $conditionnement, 5,5,'','').$conditionnement_unit;
		    
		}
        else{
            $conditionnement_label=$conditionnement_label_edit='';
        }
		
        if($TAssetOFLine->type == "NEEDED" && $type == "NEEDED"){
			$stock_needed = TAssetOF::getProductStock($product->id);

			$TRes[]= array(
				'id'=>$TAssetOFLine->getId()
				,'idprod'=>$form->hidden('TAssetOFLine['.$k.'][fk_product]', $product->id)
				,'lot_number'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][lot_number]', $TAssetOFLine->lot_number, 15,50,'type_product="NEEDED" fk_product="'.$product->id.'" rel="lot-'.$TAssetOFLine->getId().'" ','TAssetOFLineLot') : $TAssetOFLine->lot_number
				,'libelle'=>$product->getNomUrl(1).' '.$product->label.' - '
				            .($stock_needed>0 ? $langs->trans("Stock")." : ".$stock_needed : '<span style="color:red;font-weight:bold;">'.$langs->trans("Stock")." : ".$stock_needed.'</span>'  )._fiche_ligne_asset($PDOdb,$form, $of, $TAssetOFLine, 'NEEDED')
				
				,'qty_needed'=>$TAssetOFLine->qty_needed.$conditionnement_label
				,'qty'=>(($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,50) : $TAssetOFLine->qty)
				,'qty_used'=>(($of->status=='OPEN') ? $form->texte('', 'TAssetOFLine['.$k.'][qty_used]', $TAssetOFLine->qty_used, 5,50) : $TAssetOFLine->qty_used)
				,'qty_toadd'=> $TAssetOFLine->qty - $TAssetOFLine->qty_used
				,'workstations'=> $conf->workstation->enabled ? $TAssetOFLine->visu_checkbox_workstation($db, $of, $form, 'TAssetOFLine['.$k.'][fk_workstation][]') : ''
				,'delete'=> ($form->type_aff=='edit' && $of->status=='DRAFT') ? '<a href="javascript:deleteLine('.$TAssetOFLine->getId().',\'NEEDED\');">'.img_picto('Supprimer', 'delete.png').'</a>' : ''
				,'fk_entrepot' => !empty($conf->global->ASSET_MANUAL_WAREHOUSE) && $of->status == 'DRAFT' && $form->type_aff == 'edit' ? $formProduct->selectWarehouses($TAssetOFLine->fk_entrepot, 'TAssetOFLine['.$k.'][fk_entrepot]', '', 0, 0, $TAssetOFLine->fk_product) : $TAssetOFLine->getLibelleEntrepot($PDOdb)
                ,'note_private'=>(($of->status=='DRAFT') ? $form->zonetexte('', 'TAssetOFLine['.$k.'][note_private]', $TAssetOFLine->note_private, 50,1) : $TAssetOFLine->note_private)
                
			);
		}
		elseif($TAssetOFLine->type == "TO_MAKE" && $type == "TO_MAKE"){
			if(empty($TAssetOFLine->TFournisseurPrice)) {
				
				$TAssetOFLine->loadFournisseurPrice($PDOdb);
			}
			
			
			
			// Permet de sélectionner par défaut "(Fournisseur "Interne" => Fabrication interne)" si le produit TO_MAKE n'a pas de stock lorsqu'on est en mode edit et que la ligne TO_MAKE n'a pas encore de prix fournisseur enregistré
			dol_include_once('/product/class/product.class.php');
			$p = new Product($db);
			$selected = 0;
			
			if($p->fetch($TAssetOFLine->fk_product)) {
				$p->load_stock();
				$p->stock_reel;
				if($TAssetOFLine->type === 'TO_MAKE' && $p->stock_reel <= 0 && $_REQUEST['action'] === 'edit') $selected = -2;
			}
			// *************************************************************
			
			
			
			
			$Tab=array();
			foreach($TAssetOFLine->TFournisseurPrice as &$objPrice) {
				
				$label = "";

				//Si on a un prix fournisseur pour le produit
				if($objPrice->price > 0)
				{
					$unit = $objPrice->quantity == 1 ? 'Unité' : 'Unités';
					$label .= floatval($objPrice->price).' '.$conf->currency.' - '.$objPrice->quantity.' '.$unit.' -';
				}
				
				//Affiche le nom du fournisseur
				$label .= ' (Fournisseur "'.utf8_encode ($objPrice->name).'"';

				//Prix unitaire minimum si renseigné dans le PF
				if($objPrice->quantity > 0){
					' '.$objPrice->quantity.' pièce(s) min,';
				} 
				
				//Affiche le type du PF :
				if($objPrice->compose_fourni){//			soit on fabrique les composants
					$label .= ' => Fabrication interne';
				}
				elseif($objPrice->quantity <= 0){//			soit on a le produit finis déjà en stock
					$label .= ' => Sortie de stock';
				}

				if($objPrice->quantity > 0){//				soit on commande a un fournisseur
					$label .= ' => Commande fournisseur';
				}
				
				$label .= ")";

				$Tab[ $objPrice->rowid ] = array(
												'label' => $label,
												'compose_fourni' => ($objPrice->compose_fourni) ? $objPrice->compose_fourni : 0
											);

			}

			if ($conf->nomenclature->enabled) {
				dol_include_once('/nomenclature/class/nomenclature.class.php');
				
				if ($of->status == 'DRAFT' && !$TAssetOFLine->nomenclature_valide) {
					$TNomenclature = TNomenclature::get($PDOdb, $TAssetOFLine->fk_product, true);
					
					if(count($TNomenclature) > 0 ) {
						$nomenclature = '<div>'.$form->combo('', 'TAssetOFLine['.$k.'][fk_nomenclature]', $TNomenclature, $TAssetOFLine->fk_nomenclature);
					
						if ($form->type_aff=='edit') {
							$nomenclature .= '<a href="#" class="valider_nomenclature" data-id_of="' . $of->getId() . '" data-product="' . $TAssetOFLine->fk_product . '" data-of_line="' . $TAssetOFLine->rowid . '">Valider</a>';
						}
						$nomenclature.='</div>';
					}
					else{
						$nomenclature='';
					}
					
				} else {
					$n=new TNomenclature;
					$n->load($PDOdb, $TAssetOFLine->fk_nomenclature);
					$nomenclature = '<div>' .(String) $n;
					$picture = ($TAssetOFLine->nomenclature_valide ? 'ok.png' : 'no.png');
					$nomenclature .= ' <img src="img/' . $picture . '" style="padding-left: 2px; vertical-align: middle;" /></div>';
				}
				
				
			}
			
			//($of->status=='DRAFT') ? $form->combo('', 'TAssetOFLine['.$k.'][fk_nomenclature]', _getArrayNomenclature($PDOdb, $TAssetOFLine), $TAssetOFLine->fk_nomenclature) : _getTitleNomenclature($PDOdb, $TAssetOFLine->fk_nomenclature)
			$stock_tomake = TAssetOF::getProductStock($product->id);
			
			$TRes[]= array(
				'id'=>$TAssetOFLine->getId()
				,'idprod'=>$form->hidden('TAssetOFLine['.$k.'][fk_product]', $product->id)
				,'lot_number'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][lot_number]', $TAssetOFLine->lot_number, 15,50,'type_product="TO_MAKE" fk_product="'.$product->id.'"','TAssetOFLineLot') : $TAssetOFLine->lot_number
				,'libelle'=>$product->getNomUrl(1).' '.$product->label.' - '.$langs->trans("Stock")." : "
				        .$stock_tomake._fiche_ligne_asset($PDOdb,$form, $of, $TAssetOFLine, false)
		        ,'nomenclature'=>$nomenclature
				,'addneeded'=> ($form->type_aff=='edit' && $of->status=='DRAFT') ? '<a href="#null" statut="'.$of->status.'" onclick="addAllLines('.$of->getId().','.$TAssetOFLine->getId().',this);">'.img_picto('Mettre à jour les produits nécessaires', 'previous.png').'</a>' : ''
				,'qty'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,5,'','').$conditionnement_label_edit : $TAssetOFLine->qty.$conditionnement_label 
				,'fk_product_fournisseur_price' => $form->combo('', 'TAssetOFLine['.$k.'][fk_product_fournisseur_price]', $Tab, ($TAssetOFLine->fk_product_fournisseur_price != 0) ? $TAssetOFLine->fk_product_fournisseur_price : $selected, 1, '', 'style="max-width:250px;"')
				,'delete'=> ($form->type_aff=='edit' && $of->status=='DRAFT') ? '<a href="#null" onclick="deleteLine('.$TAssetOFLine->getId().',\'TO_MAKE\');">'.img_picto('Supprimer', 'delete.png').'</a>' : ''
				,'fk_entrepot' => !empty($conf->global->ASSET_MANUAL_WAREHOUSE) && $of->status == 'DRAFT' && $form->type_aff == 'edit' ? $formProduct->selectWarehouses($TAssetOFLine->fk_entrepot, 'TAssetOFLine['.$k.'][fk_entrepot]', '', 0, 0, $TAssetOFLine->fk_product) : $TAssetOFLine->getLibelleEntrepot($PDOdb)
			);
		}
	}
	
	return $TRes;
}

function _fiche_ligne_asset(&$PDOdb,&$form,&$of, &$assetOFLine, $type='NEEDED') 
{
    global $conf;
    
    if(!$conf->global->USE_LOT_IN_OF) return '';
    
    $TAsset = $assetOFLine->getAssetLinked($PDOdb);
    
    $r='<div>';
    
    if($of->status=='DRAFT' && $form->type_aff == 'edit' && $type=='NEEDED') 
    {
    	$url = dol_buildpath('asset/fiche_of.php?id='.$of->getId().'&idLine='.$assetOFLine->getId().'&action=addAssetLink&idAsset=', 2);
		// Pour le moment au limite au besoin, la création reste en dure, à voir
		$r.=$form->texte('', 'TAssetOFLine['.$assetOFLine->getId().'][new_asset]', '', 10,255,' title="Ajouter un équipement" fk_product="'.$assetOFLine->fk_product.'" rel="add-asset" fk-asset-of-line="'.$assetOFLine->getId().'" ')
			.'<a href="" base-href="'.$url.'">'.img_right('lier').'</a>'
			.'<br/>';
    }
   
    foreach($TAsset as &$asset) 
    {
        $r .= $asset->getNomUrl(1,1);
    
        if($of->status=='DRAFT' && $form->type_aff == 'edit' && $type=='NEEDED') 
        {
            $r.=' <a href="?id='.$of->getId().'&idLine='.$assetOFLine->getId().'&idAsset='.$asset->getId().'&action=deleteAssetLink">'.img_delete('Suppresion du lien').'</a>';   
        } 
    }
    
    $r.='</div>';
    
    return $r;
        
}

function _fiche(&$PDOdb, &$assetOf, $mode='edit',$fk_product_to_add=0,$fk_nomenclature=0) {
	global $langs,$db,$conf,$user,$hookmanager;
	/***************************************************
	* PAGE
	*
	* Put here all code to build page
	****************************************************/
	
	$parameters = array('id'=>$assetOf->getId());
	$reshook = $hookmanager->executeHooks('doActions',$parameters,$assetOf,$mode);    // Note that $action and $object may have been modified by hook
	
	//pre($assetOf,true);
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'fiche', $langs->trans('OFAsset'));
	
	?><style type="text/css">
		#assetChildContener .OFMaster {
			
			background:#fff;
			-webkit-box-shadow: 4px 4px 5px 0px rgba(50, 50, 50, 0.52);
			-moz-box-shadow:    4px 4px 5px 0px rgba(50, 50, 50, 0.52);
			box-shadow:         4px 4px 5px 0px rgba(50, 50, 50, 0.52);
			
			margin-bottom:20px;
		}
		
	</style>
		<div class="OFContent" rel="<?php echo $assetOf->getId() ?>">	<?php
	
	$TPrixFournisseurs = array();
	
	//$form=new TFormCore($_SERVER['PHP_SELF'],'formeq'.$assetOf->getId(),'POST');
	
	//Affichage des erreurs
	if(!empty($assetOf->errors)){
		?>
		<br><div class="error">
		<?php
		foreach($assetOf->errors as $error){
			echo $error."<br>";
			setEventMessage($error,'errors');
		}
		$assetOf->errors = array();
		?>
		</div><br>
		<?php
		
		
	}	
	
	$form=new TFormCore();
	$form->Set_typeaff($mode);
	
	$doliform = new Form($db);    
	
	if(!empty($_REQUEST['fk_product'])) echo $form->hidden('fk_product', $_REQUEST['fk_product']);
	
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$PDOdb = new TPDOdb;
	
	$TNeeded = array();
	$TToMake = array();
	
	$TNeeded = _fiche_ligne($form, $assetOf, "NEEDED");
	$TToMake = _fiche_ligne($form, $assetOf, "TO_MAKE");
	
	$TIdCommandeFourn = $assetOf->getElementElement($PDOdb);
	
	$HtmlCmdFourn = '';
	
	if(count($TIdCommandeFourn)){
		foreach($TIdCommandeFourn as $idcommandeFourn){
			$cmd = new CommandeFournisseur($db);
			$cmd->fetch($idcommandeFourn);

			$HtmlCmdFourn .= $cmd->getNomUrl(1)." - ".$cmd->getLibStatut(0);
		}
	}
	
	ob_start();
	$doliform->select_produits('','fk_product','',$conf->product->limit_size,0,-1,2,'',3,array());
	$select_product = ob_get_clean();
	
	$Tid = array();
	//$Tid[] = $assetOf->rowid;
	if($assetOf->getId()>0) $assetOf->getListeOFEnfants($PDOdb, $Tid);
	
	$TWorkstation=array();
	foreach($assetOf->TAssetWorkstationOF as $k => &$TAssetWorkstationOF) {
		$ws = &$TAssetWorkstationOF->ws;
		
		$TWorkstation[]=array(
			'libelle'=>'<a href="'.dol_buildpath('workstation/workstation.php?id='.$ws->rowid.'&action=view', 2).'">'.$ws->name.'</a>'
			,'fk_user' => visu_checkbox_user($PDOdb, $form, $ws->fk_usergroup, $TAssetWorkstationOF->users, 'TAssetWorkstationOF['.$k.'][fk_user][]', $assetOf->status)
			,'fk_project_task' => visu_project_task($db, $TAssetWorkstationOF->fk_project_task, $form->type_aff, 'TAssetWorkstationOF['.$k.'][progress]')
			,'fk_task' => visu_checkbox_task($PDOdb, $form, $TAssetWorkstationOF->fk_asset_workstation, $TAssetWorkstationOF->tasks,'TAssetWorkstationOF['.$k.'][fk_task][]', $assetOf->status)
			,'nb_hour'=> ($assetOf->status=='DRAFT' && $mode == "edit") ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour]', $TAssetWorkstationOF->nb_hour,3,10) : ($conf->global->ASSET_USE_CONVERT_TO_TIME ? convertSecondToTime($TAssetWorkstationOF->nb_hour * 3600) : $TAssetWorkstationOF->nb_hour )  
			,'nb_hour_real'=>($assetOf->status=='OPEN' && $mode == "edit") ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour_real]', $TAssetWorkstationOF->nb_hour_real,3,10) : ($conf->global->ASSET_USE_CONVERT_TO_TIME ? convertSecondToTime($TAssetWorkstationOF->nb_hour_real * 3600) : $TAssetWorkstationOF->nb_hour_real)
			,'delete'=> ($mode=='edit' && $assetOf->status=='DRAFT') ? '<a href="javascript:deleteWS('.$assetOf->getId().','.$TAssetWorkstationOF->getId().');">'.img_picto('Supprimer', 'delete.png').'</a>' : ''
			,'note_private'=>($assetOf->status=='DRAFT' && $mode == 'edit') ? $form->zonetexte('','TAssetWorkstationOF['.$k.'][note_private]', $TAssetWorkstationOF->note_private,50,1) : $TAssetWorkstationOF->note_private
			,'rang'=>($assetOf->status=='DRAFT' && $mode == "edit") ? $form->texte('','TAssetWorkstationOF['.$k.'][rang]', $TAssetWorkstationOF->rang,3,10)  : $TAssetWorkstationOF->rang 
			,'id'=>$ws->getId()
		);
		
	}
	
	$client=new Societe($db);
	if($assetOf->fk_soc>0) $client->fetch($assetOf->fk_soc);
	
	$commande=new Commande($db);
	if($assetOf->fk_commande>0) $commande->fetch($assetOf->fk_commande);
	
	$TOFParent = array_merge(array(0=>'')  ,$assetOf->getCanBeParent($PDOdb));

	$hasParent = false;
	if (!empty($assetOf->fk_assetOf_parent))
	{
		$TAssetOFParent = new TAssetOF;
		$TAssetOFParent->load($PDOdb, $assetOf->fk_assetOf_parent);
		$hasParent = true;
	}
	
	$parameters = array('id'=>$assetOf->getId());
	$reshook = $hookmanager->executeHooks('formObjectOptions',$parameters,$assetOf,$mode);    // Note that $action and $object may have been modified by hook
	
	if($fk_product_to_add>0) {
		$product_to_add = new Product($db);
		$product_to_add->fetch($fk_product_to_add);
		
		$link_product_to_add = $product_to_add->getNomUrl(1).' '.$product_to_add->label;
		$quantity_to_create = $form->texte('', 'quantity_to_create', 1, 3, 255);
	}
	else{
		$link_product_to_add = '';
		$quantity_to_create = '';
	}
	
	
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array(
			'TNeeded'=>$TNeeded
			,'TTomake'=>$TToMake
			,'workstation'=>$TWorkstation
		)
		,array(
			'assetOf'=>array(
				'id'=> $assetOf->getId()
				,'numero'=> ($assetOf->getId() > 0) ? '<a href="fiche_of.php?id='.$assetOf->getId().'">'.$assetOf->getNumero($PDOdb).'</a>' : $assetOf->getNumero($PDOdb)
				,'ordre'=>$form->combo('','ordre',TAssetOf::$TOrdre,$assetOf->ordre)
				,'fk_commande'=>($assetOf->fk_commande==0) ? '' : $commande->getNomUrl(1)
				//,'statut_commande'=> $commande->getLibStatut(0)
				,'commande_fournisseur'=>$HtmlCmdFourn
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$assetOf->temps_estime_fabrication
				,'temps_reel_fabrication'=>$assetOf->temps_reel_fabrication
				
				,'fk_soc'=> ($mode=='edit') ? $doliform->select_company($assetOf->fk_soc,'fk_soc','client=1',1) : (($client->id) ? $client->getNomUrl(1) : '')
				,'fk_project'=>(!empty($conf->global->ASSET_USE_PROJECT_TASK)) ? custom_select_projects(-1, $assetOf->fk_project, 'fk_project',$mode) : ''
				
				,'note'=>$form->zonetexte('', 'note', $assetOf->note, 80,5)
				
				,'quantity_to_create'=>$quantity_to_create
				,'product_to_create'=>$link_product_to_add
				
				,'status'=>$form->combo('','status',TAssetOf::$TStatus,$assetOf->status)
				,'statustxt'=>TAssetOf::$TStatus[$assetOf->status]
				,'idChild' => (!empty($Tid)) ? '"'.implode('","',$Tid).'"' : ''
				,'url' => dol_buildpath('/asset/fiche_of.php', 2)
				,'url_liste' => ($assetOf->getId()) ? dol_buildpath('/asset/fiche_of.php?id='.$assetOf->getId(), 2) : dol_buildpath('/asset/liste_of.php', 2)
				,'fk_product_to_add'=>$fk_product_to_add
				,'fk_nomenclature'=>$fk_nomenclature
				,'fk_assetOf_parent'=>($assetOf->fk_assetOf_parent ? $assetOf->fk_assetOf_parent : '')
				,'link_assetOf_parent'=>($hasParent ? '<a href="'.dol_buildpath('/asset/fiche_of.php?id='.$TAssetOFParent->rowid, 2).'">'.$TAssetOFParent->numero.'</a>' : '')
				
				,'mo_cost'=>price($assetOf->mo_cost,0,'',1,-1,2)
				,'compo_cost'=>price($assetOf->compo_cost,0,'',1,-1,2)
			)
			,'view'=>array(
				'mode'=>$mode
				,'status'=>$assetOf->status
				,'ASSET_USE_MOD_NOMENCLATURE'=>(int) $conf->nomenclature->enabled
				,'select_product'=>$select_product
				,'select_workstation'=>$form->combo('', 'fk_asset_workstation', TWorkstation::getWorstations($PDOdb), -1)
				//,'select_workstation'=>$form->combo('', 'fk_asset_workstation', TAssetWorkstation::getWorstations($PDOdb), -1) <= assetworkstation			
				,'actionChild'=>($mode == 'edit')?__get('actionChild','edit'):__get('actionChild','view')
				,'use_lot_in_of'=>(int) $conf->global->USE_LOT_IN_OF
				,'use_project_task'=>(int) $conf->global->ASSET_USE_PROJECT_TASK
				,'defined_user_by_workstation'=>(int) $conf->global->ASSET_DEFINED_USER_BY_WORKSTATION
				,'defined_task_by_workstation'=>(int) $conf->global->ASSET_DEFINED_OPERATION_BY_WORKSTATION
				,'defined_workstation_by_needed'=>(int) $conf->global->ASSET_DEFINED_WORKSTATION_BY_NEEDED
				,'defined_manual_wharehouse'=>(int) $conf->global->ASSET_MANUAL_WAREHOUSE
				,'hasChildren' => (int) !empty($Tid)
				,'user_id'=>$user->id
				,'workstation_module_activate'=>(int) $conf->workstation->enabled
				,'show_cost'=>(int)$user->rights->asset->of->price
			)
		)
	);
	
	echo $form->end_form();	
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

function _fiche_ligne_control(&$PDOdb, $fk_assetOf, $assetOf=-1)
{
	$res = array();
	
	if ($assetOf == -1)
	{
		$sql = 'SELECT rowid as id, libelle, question, type, "" as response, "" as id_assetOf_control FROM '.MAIN_DB_PREFIX.'asset_control WHERE rowid NOT IN (SELECT fk_control FROM '.MAIN_DB_PREFIX.'assetOf_control WHERE fk_assetOf ='.(int) $fk_assetOf.')';	
	}
	else 
	{
		if (empty($assetOf->TAssetOFControl)) return $res;
		
		$ids = array();
		foreach ($assetOf->TAssetOFControl as $ofControl)
		{
			$ids[] = $ofControl->getId();
		}
		
		$sql = 'SELECT c.rowid as id, c.libelle, c.question, c.type, ofc.response, ofc.rowid as id_assetOf_control FROM '.MAIN_DB_PREFIX.'asset_control c';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'assetOf_control ofc ON (ofc.fk_control = c.rowid)';
		$sql.= ' WHERE ofc.rowid IN ('.implode(',', $ids).')';
		
	}
	
	$PDOdb->Execute($sql);
	while ($PDOdb->Get_line())
	{
		$res[] = array(
			'id' => $PDOdb->Get_field('id')
			,'libelle' => '<a href="'.DOL_URL_ROOT.'/custom/asset/control.php?id='.$PDOdb->Get_field('id').'">'.$PDOdb->Get_field('libelle').'</a>'
			,'type' => TAssetControl::$TType[$PDOdb->Get_field('type')]
			,'action' => '<input type="checkbox" value="'.$PDOdb->Get_field('id').'" name="TControl[]" />'
			,'question' => $PDOdb->Get_field('question')
			,'response' => ($assetOf == -1 ? '' : $assetOf->generate_visu_control_value($PDOdb->Get_field('id'), $PDOdb->Get_field('type'), $PDOdb->Get_field('response'), 'TControlResponse['.$PDOdb->Get_field('id_assetOf_control').'][]'))
			,'delete' => '<input type="checkbox" value="'.$PDOdb->Get_field('id_assetOf_control').'" name="TControlDelete[]" />'
		);
	}
	
	return $res;
}

function _fiche_control(&$PDOdb, &$assetOf)
{
	global $langs,$db,$conf;
	
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'controle', $langs->trans('OFAsset'));
	
	/******/
	$TBS=new TTemplateTBS();
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;

	$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'POST');
	$form->Set_typeaff('view');
	
	$TControl = _fiche_ligne_control($PDOdb, $assetOf->getId());
	$TAssetOFControl = _fiche_ligne_control($PDOdb, $assetOf->getId(), $assetOf);
	
	print $TBS->render('tpl/fiche_of_control.tpl.php'
		,array(
			'TControl'=>$TControl
			,'TAssetOFControl'=>$TAssetOFControl
		)
		,array(
			'assetOf'=>array(
				'id'=>(int) $assetOf->getId()
			)
			,'view'=>array(
				'nbTControl'=>count($TControl)
				,'nbTAssetOFControl'=>count($TAssetOFControl)
				,'url'=>DOL_URL_ROOT.'/custom/asset/fiche_of.php'
			)
		)
	);
	
	$form->end();
	
	/******/
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}
function concat(&$pdf,$files) {
	
	foreach($files as $file)
	{
		$pagecount = $pdf->setSourceFile($file);
		
		for ($i = 1; $i <= $pagecount; $i++) {
			$tplidx = $pdf->ImportPage($i);
			$s = $pdf->getTemplatesize($tplidx);
			$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
			$pdf->useTemplate($tplidx);
		}
		
	}
	
	return $pagecount;
}