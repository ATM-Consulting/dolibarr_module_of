<?php

require('config.php');

require('./class/asset.class.php');
require('./lib/asset.lib.php');

if(!$user->rights->asset->all->lire) accessforbidden();

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("asset@asset");

// Get parameters
_action();

function _action() {
	global $user;	
	$PDOdb=new TPDOdb;

	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'new':
			case 'add':
				$assetlot=new TAssetLot;
				$assetlot->set_values($_REQUEST);
				_fiche($PDOdb,$assetlot,'new');

				break;

			case 'edit'	:
			
				$assetlot=new TAssetLot;
				$assetlot->load($PDOdb, $_REQUEST['id'], false);

				_fiche($PDOdb,$assetlot,'edit');
				break;

			case 'save':
				$assetlot=new TAssetLot;
				if(!empty($_REQUEST['id'])) $assetlot->load($PDOdb, $_REQUEST['id'], false);
				$assetlot->set_values($_REQUEST);
				$assetlot->save($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/fiche_lot.php?id=<?php echo $assetlot->rowid?>";					
				</script>
				<?
				
				break;

			case 'delete':
				$assetlot=new TAssetLot;
				$assetlot->load($PDOdb, $_REQUEST['id'], false);
				$assetlot->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/liste_lot.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
		$assetlot=new TAssetLot;
		$assetlot->load($PDOdb, $_REQUEST['id'], false);

		_fiche($PDOdb,$assetlot, 'view');
	}
	else{
		?>
		<script language="javascript">
			document.location.href="<?php echo dirname($_SERVER['PHP_SELF'])?>/liste_lot.php";					
		</script>
		<?
	}


	
	
}

function _fiche(&$PDOdb,&$assetlot, $mode='edit') {
global $langs,$db,$conf;
/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/
	
	llxHeader('',$langs->trans('AssetLot'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetlot, 'assetlot') , 'fiche', $langs->trans('AssetLot'));

	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);

	echo $form->hidden('id', $assetlot->rowid);
	if ($mode=='new'){
		echo $form->hidden('action', 'save');
	}
	echo $form->hidden('entity', $conf->entity);

	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('assetlot');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	print $TBS->render('tpl/fiche_lot.tpl.php'
		,array()
		,array(
			'assetlot'=>array(
				'id'=>$assetlot->getId()
				,'lot_number'=>$form->texte('', 'lot_number', $assetlot->lot_number, 100,255,'','','à saisir')
			)
			,'view'=>array(
				'mode' => $mode
			)
		)
	);

	echo $form->end_form();
	// End of page
	
	_liste_asset($PDOdb,$assetlot);
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

//Affiche les équipements du lot
function _liste_asset(&$PDOdb,&$assetlot){
	
	global $langs,$db,$user,$ASSET_LINK_ON_FIELD;

	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

	if(defined('ASSET_LIST_FIELDS')){
		$fields = ASSET_LIST_FIELDS;
	}	
	else{
		$fields ="e.rowid as 'ID',e.serial_number, e.lot_number,p.rowid as 'fk_product',p.label, e.contenancereel_value as 'contenance', e.contenancereel_units as 'unite', e.date_cre as 'Création'";
	} 

	$r = new TSSRenderControler($assetlot);

	$sql="SELECT ".$fields.'
		  FROM ((llx_asset e LEFT OUTER JOIN llx_product p ON (e.fk_product=p.rowid))
				LEFT OUTER JOIN llx_societe s ON (e.fk_soc=s.rowid))
		  WHERE e.lot_number = "'.$assetlot->lot_number.'"';


	$r->liste($PDOdb, $sql, array(
		'limit'=>array(
			'nbLine'=>'30'
		)
		,'subQuery'=>array()
		,'link'=>array(
			'nom'=>'<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid=@fk_soc@">'.img_picto('','object_company.png','',0).' @val@</a>'
			,'serial_number'=>'<a href="fiche.php?id=@ID@">@val@</a>'
			,'label'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id=@fk_product@">'.img_picto('','object_product.png','',0).' @val@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array('Date garantie'=>'date','Date dernière intervention'=>'date', 'Date livraison'=>'date', 'Création'=>'date')
		,'liste'=>array(
			'titre'=>'Liste des '.$langs->trans('AssetInLot')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messageNothing'=>"Il n'y a aucun ".$langs->trans('Asset')." à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
		,'title'=>array(
			'serial_number'=>'Numéro de série'
			,'nom'=>'Société'
			,'label'=>'Produit'
			,'lot_number'=>'Numéro de Lot'
			,'contenance'=>'Contenance Actuelle'
			,'unite'=>'Unité'
		)
		,'search'=>array(
			'serial_number'=>true
			,'nom'=>array('recherche'=>true, 'table'=>'s')
			,'label'=>array('recherche'=>true, 'table'=>'')
		)
		,'eval'=>array(
			'unite'=>'measuring_units_string(@val@,"weight")'
		)
	));

}

?>
