<?php

require('config.php');

require('./class/asset.class.php');
require('./class/ordre_fabrication_asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


if(!$user->rights->asset->all->lire) accessforbidden();
if(!$user->rights->asset->of->write) accessforbidden();


// Load traductions files requiredby by page
$langs->load("other");
$langs->load("asset@asset");

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

	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'new':
			case 'add':
				$assetOf=new TAssetOF;
				$assetOf->set_values($_REQUEST);
				_fiche($assetOf,'new');

				break;

			case 'edit'	:
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id']);

				_fiche($assetOf,'edit');
				break;

			case 'save':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->set_values($_REQUEST);
				
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						$assetOf->TAssetOFLine[$k]->set_values($row);
					}
				}
				
				$assetOf->save($PDOdb);
				_fiche($assetOf, 'view');

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
				
				
				$assetOf->save($PDOdb);
				_fiche($assetOf, 'view');

				break;
				
			case 'lancer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "OPEN";
				$assetOf->openOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($assetOf, 'view');

				break;
				
			case 'terminer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "CLOSE";
				$assetOf->closeOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($assetOf, 'view');
				break;
				
			case 'delete':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				
				//$PDOdb->db->debug=true;
				$assetOf->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste_of.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id'])) {
		$assetOf=new TAssetOF;
		$assetOf->load($PDOdb, $_REQUEST['id'], false);
		
		_fiche($assetOf, 'view');
	}
	
}

function TAssetOFLineAsArray(&$form, &$of, $type){
		global $db;
		
		$TRes = array();
		
		foreach($of->TAssetOFLine as $k=>$TAssetOFLine){
			$product = new Product($db);
			$product->fetch($TAssetOFLine->fk_product);
			
			if($TAssetOFLine->type == "NEEDED" && $type == "NEEDED"){
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'qty_needed'=>$TAssetOFLine->qty
					,'qty'=>$form->texte('', 'TAssetOFLine['.$k.'][qty_used]', $TAssetOFLine->qty_used, 5,5)
					,'qty_toadd'=> $TAssetOFLine->qty - $TAssetOFLine->qty_used
					,'delete'=> '<a href="#null" onclick="deleteLine('.$TAssetOFLine->getId().',\'NEEDED\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
			elseif($TAssetOFLine->type == "TO_MAKE" && $type == "TO_MAKE"){
			
				$Tab=array();
				foreach($TAssetOFLine->TFournisseurPrice as &$objPrice) {
						
					$Tab[ $objPrice->rowid ] = ($objPrice->price>0 ? floatval($objPrice->price).' €' : '') .' (Fournisseur "'.$objPrice->name.'", '.($objPrice->quantity >0 ? $objPrice->quantity.' pièce(s) min,' : '').' '.($objPrice->compose_fourni ? 'composé fourni' : 'composé non fourni' ).')';
					
				}	
				
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'idProd'=>$product->id
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'addneeded'=> '<a href="#null" onclick="addAllLines('.$TAssetOFLine->getId().',this);">'.img_picto('Ajout des produit nécessaire', 'previous.png').'</a>'
					,'qty'=>$form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,5,'','')
					,'fk_product_fournisseur_price'=>$form->combo('', 'TAssetOFLine['.$k.'][fk_product_fournisseur_price]', $Tab, $TAssetOFLine->fk_product_fournisseur_price )
					,'delete'=> '<a href="#null" onclick="deleteLine('.$TAssetOFLine->getId().',\'TO_MAKE\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
		}
		
		return $TRes;
}



function _fiche(&$assetOf, $mode='edit') {
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
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq'.$assetOf->getId(),'POST');
	$form->Set_typeaff($mode);
	$doliform = new Form($db);
	
	//Ajout des champs hidden
	echo $form->hidden('id', $assetOf->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'save');
	}
	else {echo $form->hidden('action', 'save');}
	echo $form->hidden('entity', $conf->entity);
	if(!empty($_REQUEST['fk_product'])) echo $form->hidden('fk_product', $_REQUEST['fk_product']);
	
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$PDOdb = new TPDOdb;
	
	
	
	$form2 = new TFormCore();
	if($assetOf->status != "DRAFT")
		$form2->Set_typeaff('view');
	else
		$form2->Set_typeaff($mode);
	
	$TNeeded = array();
	$TToMake = array();
	
	$TNeeded = TAssetOFLineAsArray($form2, $assetOf, "NEEDED");
	$TToMake = TAssetOFLineAsArray($form2, $assetOf, "TO_MAKE");
	
	ob_start();
	$html=new Form($db);
	$html->select_produits('','fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
	$select_product = ob_get_clean();
	
	$Tid = array();
	//$Tid[] = $assetOf->rowid;
	$assetOf->getListeOFEnfants($PDOdb, $Tid, $assetOf->rowid);
	
	
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array(
			'TNeeded'=>$TNeeded
			,'TTomake'=>$TToMake
		)
		,array(
			'assetOf'=>array(
				'id'=> $assetOf->getId()
				,'numero'=> ($mode=='edit') ? $form->texte('', 'numero', $assetOf->numero, 20,255,'','','à saisir') : '<a href="fiche_of.php?id='.$assetOf->getId().'">'.$assetOf->numero.'</a>'
				,'ordre'=>$form->combo('','ordre',$assetOf->TOrdre,$assetOf->ordre)
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$form->texte('','temps_estime_fabrication',$assetOf->temps_estime_fabrication, 12,10,'','','0')
				,'temps_reel_fabrication'=>$form->texte('','temps_reel_fabrication', $assetOf->temps_reel_fabrication, 12,10,'','','0')
				//,'fk_asset_workstation'=>$form->combo('','fk_asset_workstation',TAssetWorkstation::getWorstations($PDOdb),$assetOf->fk_asset_workstation)
				
				//,'fk_user'=>$doliform->select_users('','fk_user')
				,'status'=>$form->combo('','status',$assetOf->TStatus,$assetOf->status)
				,'idChild' =>implode(',',$Tid)
			)
			,'view'=>array(
				'mode'=>$mode
				,'status'=>$assetOf->status
				,'select_product'=>$select_product
				
			)
		)
	);
	
	
	
	
	echo $form->end_form();
	
	
	
	
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}
