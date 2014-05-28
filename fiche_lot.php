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
				_fiche($assetlot,'new');

				break;

			case 'edit'	:
			
				$assetlot=new TAssetLot;
				$assetlot->load($PDOdb, $_REQUEST['id'], false);

				_fiche($assetlot,'edit');
				break;

			case 'save':
				$assetlot=new TAssetLot;
				if(!empty($_REQUEST['id'])) $assetlot->load($PDOdb, $_REQUEST['id'], false);
				$assetlot->set_values($_REQUEST);
				$assetlot->save($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/fiche_lot.php?id=<?=$assetlot->rowid?>";					
				</script>
				<?
				
				break;

			case 'delete':
				$assetlot=new TAssetLot;
				$assetlot->load($PDOdb, $_REQUEST['id'], false);
				$assetlot->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste_lot.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
		$assetlot=new TAssetLot;
		$assetlot->load($PDOdb, $_REQUEST['id'], false);

		_fiche($assetlot, 'view');
	}
	else{
		?>
		<script language="javascript">
			document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste_lot.php";					
		</script>
		<?
	}


	
	
}

function _fiche(&$assetlot, $mode='edit') {
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
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}
?>
