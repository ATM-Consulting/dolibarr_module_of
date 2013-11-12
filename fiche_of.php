<?php

require('config.php');

require('./class/asset.class.php');
require('./class/ordre_fabrication_asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


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
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_of.php?id=<?=$assetOf->rowid?>";					
				</script>
				<?
				
				break;
				
			/*case 'clone':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id'], false);
				$asset->load_liste_type_asset($PDOdb);
				$asset->load_asset_type($PDOdb);
				$asset->reinit();
				$asset->serial_number.='(copie)';
				//$PDOdb->db->debug=true;
				$asset->save($PDOdb);
				
				_fiche($asset,'view');
				
				break;*/
				
			case 'delete':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				
				//$PDOdb->db->debug=true;
				$assetOf->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste.php?delete_ok=1";					
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

function _fiche(&$assetOf, $mode='edit') {
	global $langs,$db,$conf;
	/***************************************************
	* PAGE
	*
	* Put here all code to build page
	****************************************************/
	
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'fiche', $langs->trans('AssetOF'));
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	
	echo $form->hidden('id', $assetOf->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'edit');
	}
	else {echo $form->hidden('action', 'save');}
	echo $form->hidden('entity', $conf->entity);

	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;

	$TFields=array();
	
	/*echo '<pre>';
	print_r($TFields);
	echo '</pre>';exit;*/
	
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array()
		,array(
			'assetOf'=>array(
				'id'=>$assetOf->getId()
				,'numero'=>$form->texte('', 'numero', $assetOf->numero, 100,255,'','','à saisir')
				,'ordre'=>$form->combo('','ordre',$assetOf->TOrdre,1)
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$form->texte('','temps_estime_fabrication',$assetOf->temps_estime_fabrication, 12,10,'','','0,00')
				,'temps_reel_fabrication'=>$form->texte('','temps_reel_fabrication', $assetOf->temps_reel_fabrication, 12,10,'','','0,00')
				,'fk_asset_workstation'=>$form->combo('','fk_asset_workstation',$assetOf->TWorkstation,1)
				//,'fk_user'=>$form->combo('','fk_user',$assetOf->TWorkstation,1)
			)
			/*,'stock'=>array(
				'type_mvt'=>$form2->combo('','type_mvt',array(''=>'','retrait'=>'Retrait','ajout'=>'Ajout'),'')
				,'qty'=>$form2->texte('', 'qty', '', 12,10,'','','')
				,'commentaire_mvt'=>$form2->zonetexte('','commentaire_mvt','',100)
			)
			,'assetNew' =>array(
				'typeCombo'=> count($asset->TType) ? $form->combo('','fk_asset_type',$asset->TType,$asset->fk_asset_type): "Aucun type"
				,'validerType'=>$form->btsubmit('Valider', 'validerType')
				
			)
			,'affaire'=>$TAffaire
			,'view'=>array(
				'mode'=>$mode
				,'module_financement'=>(int)isset($conf->global->MAIN_MODULE_FINANCEMENT)
				,'liste'=>$liste->renderArray($PDOdb,$TAssetStock
					,array(
						  'title'=>array(
							'date_cre'=>'Date du mouvement'
							,'qty'  =>'Quantité'
							,'weight_units' => 'Unité'
							,'lot' => 'Lot'
							,'type' => 'Commentaire'
						)
						,'link'=>array_merge($ASSET_LINK_ON_FIELD,array())
					)
				)
			)*/
		)
	);
	
	echo $form->end_form();
	// End of page
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
	}

?>
