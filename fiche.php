<?php

require('config.php');

require('./class/asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';


if(!$user->rights->asset->all->lire) accessforbidden();

if(isset($conf->global->MAIN_MODULE_FINANCEMENT)) {
	dol_include_once('/financement/class/affaire.class.php');
}


// Load traductions files requiredby by page
$langs->load("companies");
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
				$asset=new TAsset;
				$asset->set_values($_REQUEST);
				_fiche($asset,'new');
				
				break;
			
			case 'edit'	:
				$asset=new TAsset;
				$asset->fk_asset_type = $_REQUEST['fk_asset_type'];
				$asset->load_asset_type($PDOdb);
				$asset->load($PDOdb, $_REQUEST['id'], false);
				
				_fiche($asset,'edit');
				break;
			
			case 'stock':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id'], false);
				
				_fiche($asset,'stock');
				break;
				
			case 'save':
				/*echo '<pre>';
				print_r($_REQUEST);
				echo '<pre>'; exit;*/
				$asset=new TAsset;
				$asset->fk_asset_type = $_REQUEST['fk_asset_type'];
				$asset->load($PDOdb, $_REQUEST['id'], false);
				
				//on vérifie que le libellé est renseigné
				if  ( empty($_REQUEST['numId']) ){
					$mesg .= '<div class="error">Le numéro Id doit être renseigné.</div>';
				}
				
				if  ( empty($_REQUEST['libelle']) ){
					$mesg .= '<div class="error">Le libellé doit être renseigné.</div>';
				}
				
				//on vérifie que les champs obligatoires sont renseignés
				foreach($asset->assetType->TField as $k=>$field) {
					if (! $field->obligatoire){
						if  ( empty($_REQUEST[$field->code]) ){
							$mesg .= '<div class="error">Le champs '.$field->libelle.' doit être renseigné.</div>';
						}
					}
				}
				
				//ensuite on vérifie ici que les champs (OBLIGATOIRE OU REMPLIS) sont bien du type attendu
				if ($mesg == ''){
					foreach($asset->assetType->TField as $k=>$field) {
						if (! $field->obligatoire || ! empty($_REQUEST[$field->code])){
							switch ($field->type){
								case 'float':
								case 'entier':
									//la conversion en entier se fera lors de la sauvegarde dans l'objet.
									if (! is_numeric($_REQUEST[$field->code]) ){
										$mesg .= '<div class="error">Le champ '.$field->libelle.' doit être un nombre.</div>';
										}
									break;
								default :
									break;
							}
						}
					}
				}
				
				
				$asset->set_values($_REQUEST);
				
				if(!isset($_REQUEST['type_mvt']))
					$asset->save($PDOdb);
				else{
					$qty = ($_REQUEST['type_mvt'] == 'retrait') ? $_REQUEST['qty'] * -1 : $_REQUEST['qty'];
					$asset->save($PDOdb,$user,$_REQUEST['commentaire_mvt'],$qty);
				}
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche.php?id=<?=$asset->rowid?>";					
				</script>
				<?
				
				break;
				
			case 'clone':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id'], false);
				$asset->load_asset_type($PDOdb);
				$asset->reinit();
				$asset->serial_number.='(copie)';
				//$PDOdb->db->debug=true;
				$asset->save($PDOdb);
				
				_fiche($asset,'view');
				
				break;
				
			case 'delete':
				$asset=new TAsset;
				$asset->load($PDOdb, $_REQUEST['id'], false);
				
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
		$asset->load($PDOdb, $_REQUEST['id'], false);
		$asset->load_asset_type($PDOdb);
		
		_fiche($asset, 'view');
	}


	
	
}

function _fiche(&$asset, $mode='edit') {
global $langs,$db,$conf, $ASSET_LINK_ON_FIELD;
/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/
	
	llxHeader('',$langs->trans('Asset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $asset, 'asset') , 'fiche', $langs->trans('Asset'));
	
	if(isset($_REQUEST['error'])) {
		?>
		<br><div class="error">Type de mouvement incorrect</div><br>
		<?
	}
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	
	$form2=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form2->Set_typeaff('edit');
	
	echo $form->hidden('id', $asset->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'edit');
	}
	else {echo $form->hidden('action', 'save');}
	echo $form->hidden('entity', $conf->entity);
	
	
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
			,'weight_units'=>measuring_units_string($stock->weight_units,"weight")
			,'lot' =>$stock->lot
			,'type'=>$stock->type
		);
		
		
	}
	
	?>
	<script type="text/javascript">
		$('#formeq').submit(function(){
			if($('#type_mvt').val() == ''){
				alert('Type de mouvement incorrect');
				return false;
			}
		})
	</script>
	<?php
	
	$TFields=array();
	
	?>
	<script type="text/javascript">
		$(document).ready(function(){
			
		<?php
		foreach($asset->assetType->TField as $k=>$field) {
			switch($field->type){
				case liste:
					$temp = $form->combo('',$field->code,$field->TListe,$asset->{$field->code});
					break;
				case checkbox:
					$temp = $form->combo('',$field->code,array('oui'=>'Oui', 'non'=>'Non'),$asset->{$field->code});
					break;
				default:
					$temp = $form->texte('', $field->code, $asset->{$field->code}, 50,255,'','','-');
					break;
			}
			
			$TFields[$k]=array(
					'libelle'=>$field->libelle
					,'valeur'=>$temp
					//champs obligatoire : 0 = obligatoire ; 1 = non obligatoire
					,'obligatoire'=>$field->obligatoire ? 'class="field"': 'class="fieldrequired"' 
				);
			
			//Autocompletion
			if($field->type != combo && $field->type != liste){
				?>
				$("#<?=$field->code; ?>").autocomplete({
					source: "script/interface.php?get=autocomplete&json=1&fieldcode=<?=$field->code; ?>",
					minLength : 1
				});
				
				<?php
			}
		}

		//Concaténation des champs dans le libelle asset
		foreach($asset->assetType->TField as $k=>$field) {
			
			if($field->inlibelle == "oui"){
				$chaineid .= "#".$field->code.", ";
				$chaineval .= "$('#".$field->code."').val().toUpperCase()+' '+";
			}
			
		}
		$chaineval = substr($chaineval, 0,-5);
		$chaineid = substr($chaineid, 0,-2);
		?>
			$('<?=$chaineid; ?>').bind("keyup change", function(e) {
				$('#libelle').val(<?=$chaineval; ?>);
			});
		});
	</script>
	<?php
	
	/*echo '<pre>';
	print_r($TFields);
	echo '</pre>';exit;*/
	
	print $TBS->render('tpl/fiche.tpl.php'
		,array(
			'assetField'=>$TFields
		)
		,array(
			'asset'=>array(
				'id'=>$asset->getId()
				/*,'reference'=>$form->texte('', 'reference', $dossier->reference, 100,255,'','','à saisir')*/ 
				,'serial_number'=>$form->texte('', 'serial_number', $asset->serial_number, 100,255,'','','à saisir')
				,'produit'=>_fiche_visu_produit($asset,$mode)
				,'societe'=>_fiche_visu_societe($asset,$mode)
				,'typehidden'=>$form->hidden('fk_asset_type', $asset->fk_asset_type)
			)
			,'stock'=>array(
				'type_mvt'=>$form2->combo('','type_mvt',array(''=>'','retrait'=>'Retrait','ajout'=>'Ajout'),'')
				,'qty'=>$form2->texte('', 'qty', '', 12,10,'','','')
				,'commentaire_mvt'=>$form2->zonetexte('','commentaire_mvt','',100)
			)
			,'assetNew' =>array(
				'typeCombo'=> count($asset->TType) ? $form->combo('','fk_rh_ressource_type',$asset->TType,$asset->fk_asset_type): "Aucun type"
				,'validerType'=>$form->btsubmit('Valider', 'validerType')
				
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
							,'qty'  =>'Quantité'
							,'weight_units' => 'Unité'
							,'lot' => 'Numéro batch'
							,'type' => 'Commentaire'
						)
						,'link'=>array_merge($ASSET_LINK_ON_FIELD,array())
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
	
	if($mode=='edit' || $mode=='new') {
		ob_start();	
		$html=new Form($db);
		$html->select_produits((!empty($_REQUEST['fk_product']))? $_REQUEST['fk_product'] :$asset->fk_product,'fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
		
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

function _fiche_visu_units(&$asset, $mode, $name,$defaut=-3) {
global $db;
	
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new FormProduct($db);
		
		echo $html->select_measuring_units($name, "weight", $asset->$name);
		//($asset->$name != "")? $asset->$name : $defaut
		
		return ob_get_clean();
		
	}
	elseif($mode=='new'){
		ob_start();	
		
		$html=new FormProduct($db);
		
		echo $html->select_measuring_units($name, "weight", $defaut);
		//($asset->$name != "")? $asset->$name : $defaut
		
		return ob_get_clean();
	}
	else{
		ob_start();	
		
		echo measuring_units_string($asset->$name, "weight");
		
		return ob_get_clean();
	}
}

?>
