<?php

require('config.php');
require('./class/asset.class.php');
require('./class/ordre_fabrication_asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


if(!$user->rights->asset->all->lire) accessforbidden();
if(!$user->rights->asset->of->write) accessforbidden();


// Load traductions files requiredby by page
$langs->load("other");

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	global $user;	
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
				
				_fiche($PDOdb, $assetOf,'edit', $fk_product);

				break;

			case 'edit'	:
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id']);

				_fiche($PDOdb,$assetOf,'edit');
				break;

			case 'save':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) {
					$assetOf->load($PDOdb, $_REQUEST['id'], false);
					$mode = 'view';
				}
				else {
					$mode = 'edit';
				}
				
				//pre($_REQUEST,true);
								
				$assetOf->set_values($_REQUEST);

				if(__get('fk_product_to_add',0)>0) {
					$assetOf->addLine($PDOdb, __get('fk_product_to_add',0), 'TO_MAKE');		
				//	print "Add ".__get('fk_product_to_add',0);			
				}
			
				
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						if(!isset( $assetOf->TAssetOFLine[$k] ))  $assetOf->TAssetOFLine[$k] = new TAssetOFLine;
						$assetOf->TAssetOFLine[$k]->set_values($row);
					}
					
					//pre($assetOf->TAssetOFLine,true);
					
					foreach($assetOf->TAssetOFLine as &$line) {
						$line->TAssetOFLine=array();
					}
					
					//pre($assetOf->TAssetOFLine,true);
				}
				
				if(!empty($_REQUEST['TAssetWorkstationOF'])) {
					foreach($_REQUEST['TAssetWorkstationOF'] as $k=>$row) {
						$assetOf->TAssetWorkstationOF[$k]->set_values($row);
					}
				}
				
				$assetOf->entity = $conf->entity;

				//pre($assetOf->TAssetOFLine,true);exit;
				
				$assetOf->save($PDOdb);
				
				_fiche($PDOdb,$assetOf, $mode);

				break;
			
			case 'valider':
				
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "VALID";				
				
				
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						$assetOf->TAssetOFLine[$k]->set_values($row);
					}
				}
				$assetOf->createOfAndCommandesFourn($PDOdb);
				//$assetOf->openOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($PDOdb,$assetOf, 'view');

				break;
				
			case 'lancer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "OPEN";

				$assetOf->setEquipement($PDOdb);

				/*echo '<pre>';
				print_r($assetOf);
				echo '</pre>'; exit;*/
				
				//$assetOf->openOF($PDOdb);
				//$assetOf->save($PDOdb);
				_fiche($PDOdb, $assetOf, 'view');

				break;
				
			case 'terminer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "CLOSE";
				$assetOf->closeOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($PDOdb,$assetOf, 'view');
				break;
				
			case 'delete':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				
				//$PDOdb->db->debug=true;
				$assetOf->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dol_buildpath('/asset/liste_of.php',1); ?>?delete_ok=1";					
				</script>
				<?
				
				break;
				
			case 'view':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
		
				_fiche($PDOdb, $assetOf, 'view');
				
				break;
			case 'createDocOF':
				
				generateODTOF($PDOdb);
								
				break;				
		}
		
	
	
	
}



function generateODTOF(&$PDOdb) {
	
	global $db;

	$assetOf=new TAssetOF;
	$assetOf->load($PDOdb, $_REQUEST['id'], false);
	foreach($assetOf as $k => $v) {
		print $k."<br />";
	}
	//exit;
	
	/*echo "<pre>";
	print_r($assetOf->TAssetWorkstationOF);
	echo "</pre>";
	
	exit;*/
	$TBS=new TTemplateTBS();
	dol_include_once("/product/class/product.class.php");

	$TToMake = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit à fabriquer
	$TNeeded = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit nécessaires
	$TWorkstations = array(); // Tableau envoyé à la fonction render contenant les informations concernant les stations de travail
	
	$societe = new Societe($db);
	$societe->fetch($assetOf->fk_soc);
	
	//pre($societe,true); exit;
	
	// On charge les tableaux de produits à fabriquer, et celui des produits nécessaires
	foreach($assetOf->TAssetOFLine as $k=>$v) {
		
		if($v->type == "TO_MAKE") {
			
			$prod = new Product($db);
			$prod->fetch($v->fk_product);
			
			$TToMake[] = array(
							'type' => $v->type
							, 'qte' => $v->qty
							, 'nomProd' => $prod->ref
							, 'designation' => $prod->label
							, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
						);
			
		}
		if($v->type == "NEEDED") {
	
			$unitLabel = "";
			
			$prod = new Product($db);
			$prod->fetch($v->fk_product);						
			
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
							'type' => $v->type
							, 'qte' => $v->qty
							, 'nomProd' => $prod->ref
							, 'designation' => $prod->label
							, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
							, 'poids' => $prod->weight
							, 'unitPoids' => $unitLabel
							, 'finished' => $prod->finished?"PM":"MP"
						);
	
			
		}

	}

	/*echo "<pre>";
	print_r($TNeeded);
	echo "</pre>";
	exit;*/

	// On charge le tableau d'infos sur les stations de travail de l'OF courant
	foreach($assetOf->TAssetWorkstationOF as $k => $v) {
		
		$TWorkstations[] = array(
							'libelle' => $v->ws->libelle
							,'nb_hour_max' => $v->ws->nb_hour_max
							,'nb_heures_prevues' => $v->nb_hour
						);
		
	}
	
	$dirName = 'OF'.$_REQUEST['id'].'('.date("d_m_Y").')';
	$dir = DOL_DATA_ROOT.'/asset/'.$dirName.'/';
	
	@mkdir($dir, 0777, true);
	
	if(defined('TEMPLATE_OF')){
		$template = TEMPLATE_OF;
	}
	else{
		$template = "templateOF.odt";
	}
	
	//echo $societe->name; exit;
	
	$TBS->render(dol_buildpath('/asset/exempleTemplate/'.$template)
		,array(
			'lignesToMake'=>$TToMake
			,'lignesNeeded'=>$TNeeded
			,'lignesWorkstation'=>$TWorkstations
		)
		,array(
			'date'=>date("d/m/Y")
			,'numeroOF'=>$assetOf->numero
			,'statutOF'=>TAssetOF::$TStatus[$assetOf->status]
			,'prioriteOF'=>TAssetOF::$TOrdre[$assetOf->ordre]
			,'date'=>date("d/m/Y")
			,'societe'=>$societe->name
		)
		,array()
		,array(
			'outFile'=>$dir.$assetOf->numero.".odt"
		)
		
	);	
	
	header("Location: ".DOL_URL_ROOT."/document.php?modulepart=asset&entity=1&file=".$dirName."/".$assetOf->numero.".odt");

}


function _fiche_ligne(&$form, &$of, $type){
		global $db, $conf;
		
		$TRes = array();
		foreach($of->TAssetOFLine as $k=>$TAssetOFLine){
			$product = new Product($db);
			$product->fetch($TAssetOFLine->fk_product);
			
			if($TAssetOFLine->type == "NEEDED" && $type == "NEEDED"){
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'idProd'=>$product->id
					,'lot_number'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][lot_number]', $TAssetOFLine->lot_number, 15,50,'','TAssetOFLineLot') : $TAssetOFLine->lot_number
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'qty_needed'=>$TAssetOFLine->qty_needed
					,'qty'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,50) : $TAssetOFLine->qty
					,'qty_used'=>($of->status=='OPEN') ? $form->texte('', 'TAssetOFLine['.$k.'][qty_used]', $TAssetOFLine->qty_used, 5,50) : $TAssetOFLine->qty_used
					,'qty_toadd'=> $TAssetOFLine->qty - $TAssetOFLine->qty_used
					,'delete'=> '<a href="javascript:deleteLine('.$TAssetOFLine->getId().',\'NEEDED\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
			elseif($TAssetOFLine->type == "TO_MAKE" && $type == "TO_MAKE"){
			
				if(empty($TAssetOFLine->TFournisseurPrice)) {
					$ATMdb=new TPDOdb;
					$TAssetOFLine->loadFournisseurPrice($ATMdb);
				}
			
				$Tab=array();
				foreach($TAssetOFLine->TFournisseurPrice as &$objPrice) {
						
					$Tab[ $objPrice->rowid ] = array(
												'label' => ($objPrice->price>0 ? floatval($objPrice->price).' '.$conf->currency : '') .' (Fournisseur "'.utf8_encode ($objPrice->name).'"'.($objPrice->quantity >0 ? $objPrice->quantity.' pièce(s) min,' : '').' '.($objPrice->compose_fourni ? 'composants a fabriquer' : '' ).')',
												'compose_fourni' => $objPrice->compose_fourni
											);
					
					/* ob_start()
					 *  ?> <option value="51" composefourni="0" ofchild="5,3,7"> $( #select :selected).each(function() {  if($(this).attr('composefourni')==1  $(this).attr('ofchild').split(',')  }) <?  
					 * $html = ob_get_clean() */
					 
				}
				
	 			/*foreach($TAssetOFLine->TFournisseurPrice as &$objPrice) {
	 				
	 				//<option value="<?=$objPrice->rowid?>" compose_fourni="<?=$objPrice->compose_fourni?1:0?>"><?($objPrice->price>0 ? floatval($objPrice->price).' '.$conf->currency : '') .' (Fournisseur "'.$objPrice->name.'", '.($objPrice->quantity >0 ? $objPrice->quantity.' pièce(s) min,' : '').' '.($objPrice->compose_fourni ? 'composé fourni' : 'composé non fourni' ).')'?></option>
	 				$option.='<option value="'.$objPrice->rowid.'" compose_fourni="'.$objPrice->compose_fourni.'">'.($objPrice->price>0 ? floatval($objPrice->price).' '.$conf->currency : '') .' (Fournisseur "'.$objPrice->name.'", '.($objPrice->quantity >0 ? $objPrice->quantity.' pièce(s) min,' : '').' '.($objPrice->compose_fourni ? 'composé fourni' : 'composé non fourni' ).')'.'</option>';	
	 				
				}*/
				
				//pre($Tab,true);
				
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'idProd'=>$product->id
					,'lot_number'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][lot_number]', $TAssetOFLine->lot_number, 15,50,'','TAssetOFLineLot') : $TAssetOFLine->lot_number
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'addneeded'=> '<a href="#null" onclick="addAllLines('.$of->getId().','.$TAssetOFLine->getId().',this);">'.img_picto('Ajout des produit nécessaire', 'previous.png').'</a>'
					,'qty'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,5,'','') : $TAssetOFLine->qty 
					,'fk_product_fournisseur_price'=>($of->status=='DRAFT') ? $form->combo('', 'TAssetOFLine['.$k.'][fk_product_fournisseur_price]', $Tab, $TAssetOFLine->fk_product_fournisseur_price ) : $Tab[$TAssetOFLine->fk_product_fournisseur_price]['label']
					
					//,'fk_product_fournisseur_price'=>($of->status=='DRAFT') ? '<select class="flat" id="TAssetOFLine['.$k.'][fk_product_fournisseur_price]" name="TAssetOFLine['.$k.'][fk_product_fournisseur_price]">'.$option.'</select>' : $Tab[$TAssetOFLine->fk_product_fournisseur_price]
					
					,'delete'=> '<a href="#null" onclick="deleteLine('.$TAssetOFLine->getId().',\'TO_MAKE\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
		}
		
		return $TRes;
}



function _fiche(&$PDOdb, &$assetOf, $mode='edit',$fk_product_to_add=0) {
	global $langs,$db,$conf;
	/***************************************************
	* PAGE
	*
	* Put here all code to build page
	****************************************************/
	
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'fiche', $langs->trans('AssetOF'));
	

	?>	<div class="OFContent" rel="<?=$assetOf->getId() ?>">	<?php
	
	$TPrixFournisseurs = array();
	
	//$form=new TFormCore($_SERVER['PHP_SELF'],'formeq'.$assetOf->getId(),'POST');
	
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
	
	if($conf->global->USE_LOT_IN_OF){
		?>
		<script type="text/javascript">
			$(document).ready(function(){
				$(".TAssetOFLineLot").autocomplete({
					source: "script/interface.php?get=autocomplete&json=1&fieldcode=lot_number",
					minLength : 1
				});
			});
		</script>
		<?php
	}
	
	ob_start();
	$doliform->select_produits('','fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
	$select_product = ob_get_clean();
	
	$Tid = array();
	//$Tid[] = $assetOf->rowid;
	if($assetOf->getId()>0) $assetOf->getListeOFEnfants($PDOdb, $Tid);
	
	
	$TWorkstation=array();
	foreach($assetOf->TAssetWorkstationOF as $k=>$TAssetWorkstationOF) {
		
		$ws = & $TAssetWorkstationOF->ws;
		
		$TWorkstation[]=array(
			'libelle'=>$ws->libelle
			,'nb_hour'=> ($assetOf->status=='DRAFT' && $mode == "edit") ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour]', $TAssetWorkstationOF->nb_hour,3,10) : $TAssetWorkstationOF->nb_hour  
			,'nb_hour_real'=>($assetOf->status=='OPEN' && $mode == "edit") ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour_real]', $TAssetWorkstationOF->nb_hour_real,3,10) : $TAssetWorkstationOF->nb_hour_real
			,'delete'=> '<a href="javascript:deleteWS('.$assetOf->getId().','.$TAssetWorkstationOF->getId().');">'.img_picto('Supprimer', 'delete.png').'</a>'
			,'id'=>$ws->getId()
		);
		
	}
	
	$client=new Societe($db);
	if($assetOf->fk_soc>0) $client->fetch($assetOf->fk_soc);
	
	$commande=new Commande($db);
	if($assetOf->fk_commande>0) $commande->fetch($assetOf->fk_commande);
	
	$TOFParent = array_merge(array(0=>'')  ,$assetOf->getCanBeParent($PDOdb));
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array(
			'TNeeded'=>$TNeeded
			,'TTomake'=>$TToMake
			,'workstation'=>$TWorkstation
		)
		,array(
			'assetOf'=>array(
				'id'=> $assetOf->getId()
				,'numero'=> ($mode=='edit') ? $form->texte('', 'numero', ($assetOf->numero) ? $assetOf->numero : 'OF'.str_pad( $assetOf->getLastId($PDOdb) +1 , 5, '0', STR_PAD_LEFT), 20,255,'','','à saisir') : '<a href="fiche_of.php?id='.$assetOf->getId().'">'.$assetOf->numero.'</a>'
				,'ordre'=>$form->combo('','ordre',TAssetOf::$TOrdre,$assetOf->ordre)
				,'fk_assetOf_parent'=>($mode=='edit') ? $form->combo('','fk_assetOf_parent',$TOFParent,$assetOf->fk_assetOf_parent) : '<a href="fiche_of.php?id='.$assetOf->fk_assetOf_parent.'">'.$TOFParent[$assetOf->fk_assetOf_parent].'</a>'
				,'fk_commande'=>($assetOf->fk_commande==0) ? '' : $commande->getNomUrl(1)
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$assetOf->temps_estime_fabrication
				,'temps_reel_fabrication'=>$assetOf->temps_reel_fabrication
				
				,'fk_soc'=> ($mode=='edit') ? $doliform->select_company($assetOf->fk_soc,'fk_soc','client=1',1) : $client->getNomUrl(1)
				
				,'note'=>$form->zonetexte('', 'note', $assetOf->note, 80,5)
				
				,'status'=>$form->combo('','status',TAssetOf::$TStatus,$assetOf->status)
				,'idChild' => (!empty($Tid)) ? '"'.implode('","',$Tid).'"' : ''
				,'url' => dol_buildpath('/asset/fiche_of.php', 2)
				,'url_liste' => ($assetOf->getId()) ? dol_buildpath('/asset/fiche_of.php?id='.$assetOf->getId(), 2) : dol_buildpath('/asset/liste_of.php', 2)
				,'fk_product_to_add'=>$fk_product_to_add
				,'fk_assetOf_parent'=>$assetOf->fk_assetOf_parent
			)
			,'view'=>array(
				'mode'=>$mode
				,'status'=>$assetOf->status
				,'select_product'=>$select_product
				,'select_workstation'=>$form->combo('', 'fk_asset_workstation', TAssetWorkstation::getWorstations($PDOdb), -1)			
				,'actionChild'=>($mode == 'edit')?__get('actionChild','edit'):__get('actionChild','view')
				,'use_lot_in_of'=>(int)$conf->global->USE_LOT_IN_OF
			)
		)
	);
	
	echo $form->end_form();
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}
