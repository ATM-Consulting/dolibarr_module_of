<?php

require('config.php');

require('./class/asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';


if(!$user->rights->asset->all->lire) accessforbidden();

if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
	dol_include_once('/financement/class/affaire.class.php');
}


// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;
	
	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'add':
				$asset=new TAsset();
				$asset->set_values($_REQUEST);
	
				$asset->save($PDOdb);
				_fiche($asset,'edit');
				
				break;
			
			case 'edit'	:
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				
				_fiche($asset,'edit');
				break;
				
			case 'save':
				/*echo '<pre>';
				print_r($_REQUEST);
				echo '<pre>'; exit;*/
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->set_values($_REQUEST);
				//print_r($_REQUEST);
				//$PDOdb->db->debug=true;
				//print_r($_REQUEST);
				
				$asset->save($PDOdb);
				
				_fiche($asset,'view');
				
				break;
				
			case 'clone':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				$asset->reinit();
				$asset->serial_number.='(copie)';
				//$PDOdb->db->debug=true;
				$asset->save($PDOdb);
				
				_fiche($asset,'view');
				
				break;
				
			case 'delete':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id']);
				
				//$PDOdb->db->debug=true;
				$asset->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id'])) {
		$asset=new TAsset;
		$asset->load($PDOdb, $_REQUEST['id']);
		
		_fiche($asset, 'view');
	}


	
	
}

function _fiche(&$asset, $mode='edit') {
global $db,$conf;
/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/
	
	llxHeader('','Flacons','','');
	
	
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	
	echo $form->hidden('id', $asset->rowid);
	echo $form->hidden('action', 'save');
	
	
	/*
	 * affichage données équipement lié à une affaire du module financement
	 */	
  	if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
  		$PDOdb=new TPDOdb;
	 	$id_affaire = $asset->getLink('affaire')->fk_document;
		$affaire=new TFin_affaire;
		$affaire->load($PDOdb, $id_affaire, false);
 		
		$TAffaire = $affaire->get_values();
 	}
	else {
		$TAffaire = array();
	}
	 
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');
	
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$TAssetStock = array();
	
	foreach($asset->TStock as &$stock) {
	
		$date = $stock->get_dtcre();
		
		$TAssetStock[]=array(
			'date_cre'=>$date
			,'qty'=>$stock->qty
			,'type'=>$stock->type
		);
		
		
	}
	
	print $TBS->render( (defined('ASSET_FICHE_TPL') ? './tpl/'.ASSET_FICHE_TPL : './tpl/fiche.tpl.php')
		,array(
		)
		,array(
			'asset'=>array(
				'id'=>$asset->getId()
				/*,'reference'=>$form->texte('', 'reference', $dossier->reference, 100,255,'','','à saisir')*/ 
				,'serial_number'=>$form->texte('', 'serial_number', $asset->serial_number, 100,255,'','','à saisir')
				,'periodicity'=>$form->texte('', 'periodicity', $asset->periodicity, 8,10,'','','à saisir')
				,'produit'=>_fiche_visu_produit($asset,$mode)
				,'societe'=>_fiche_visu_societe($asset,$mode)
				,'date_achat'=>$form->calendrier('', 'date_achat', $asset->get_date('date_achat'),10)
				,'date_shipping'=>$form->calendrier('', 'date_shipping', $asset->get_date('date_shipping'),10)
				,'date_garantie'=>$form->calendrier('', 'date_garantie', $asset->get_date('date_garantie'),10)
				,'date_last_intervention'=>$form->calendrier('', 'date_last_intervention', $asset->get_date('date_last_intervention'),10)
				,'copy_black'=>$form->texte('', 'copy_black', $asset->copy_black, 12,10,'','','0.00')
				,'copy_color'=>$form->texte('', 'copy_color', $asset->copy_black, 12,10,'','','0.00')
				,'contenance_value'=>$form->texte('', 'contenance_value', $asset->contenance_value, 12,10,'','','0.00')
				,'contenance_units'=>_fiche_visu_units($asset, $mode, 'contenance_units')
				,'contenancereel_value'=>$form->texte('', 'contenancereel_value', $asset->contenancereel_value, 12,10,'','','0.00')
				,'contenancereel_units'=>_fiche_visu_units($asset, $mode, 'contenancereel_units')
				,'lot_number'=>$form->texte('', 'lot_number', $asset->lot_number, 100,255,'','','à saisir')
			)
			,'affaire'=>$TAffaire
			,'view'=>array(
				'mode'=>$mode
				,'module_financement'=>(int)isset($conf->global->MAIN_MODULE_FINANCEMENT)
				,'head'=>dol_get_fiche_head(assetPrepareHead($asset)  , 'fiche', 'Flacon')
				,'liste'=>$liste->renderArray($PDOdb,$TAssetStock
					,array(
						  'title'=>array(
							'date_cre'=>'Date du mouvement'
							,'qty'=>'Quantité'
							,'type'=>'Type de mouvement'
						)
					)
				)
			)
		)
	);
	
	echo $form->end_form();
	// End of page
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

function _fiche_visu_produit(&$asset, $mode) {
global $db, $conf;
	
	if($mode=='edit') {
		ob_start();	
		$html=new Form($db);
		$html->select_produits($asset->fk_product,'fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_product > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
			
			$product = new Product($db);
			$product->fetch($asset->fk_product);
				
			return '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$asset->fk_product.'" style="font-weight:bold;">'.img_picto('','object_product.png', '', 0).' '. $product->label.'</a>';
		} else {
			return 'Non défini';
		}
	}
}
function _fiche_visu_societe(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			$soc->fetch($asset->fk_soc);	
				
			return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$asset->fk_soc.'" style="font-weight:bold;">'.img_picto('','object_company.png', '', 0).' '.$soc->nom.'</a>';
		} else {
			return 'Non défini';
		}
	}
}

function _fiche_visu_affaire(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			$soc->fetch($asset->fk_soc);	
				
			return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$asset->fk_soc.'" style="font-weight:bold;"><img border="0" src="'.DOL_URL_ROOT.'/theme/atm/img/object_company.png"> '.$soc->nom.'</a>';
		} else {
			return 'Non défini';
		}
	}
}

function _fiche_visu_units(&$asset, $mode, $name) {
global $db;
	
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new FormProduct($db);
		echo $html->select_measuring_units($name, "weight", $asset->$name);
		
		return ob_get_clean();
		
	}
	else{
		ob_start();	
		
		echo measuring_units_string($asset->$name, "weight");
		
		return ob_get_clean();
	}
}

?>
