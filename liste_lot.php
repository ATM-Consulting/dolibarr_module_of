<?php
	require('config.php');
	require('./class/asset.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	
	_liste();

function _liste() {
	global $langs,$db,$user,$conf;
	
	$langs->load('asset@asset');
	
	llxHeader('',$langs->trans('ListAssetLot'),'','');
	getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?= $langs->trans('AssetLotDeleted'); ?></div><br>
		<?
	}
	
	$form=new TFormCore;

	$assetlot=new TAssetLot;
	$r = new TSSRenderControler($assetlot);

	$sql="SELECT rowid, lot_number, 
				(SELECT COUNT(rowid) FROM ".MAIN_DB_PREFIX."asset WHERE lot_number = al.lot_number) as assetinlot,
				(SELECT COUNT(DISTINCT(p.rowid)) FROM ".MAIN_DB_PREFIX."product as p LEFT JOIN ".MAIN_DB_PREFIX."asset as a ON (a.fk_product = p.rowid) WHERE a.lot_number = al.lot_number) as productinlot
		  FROM ".MAIN_DB_PREFIX."assetlot as al
		  WHERE entity=".$conf->entity;

	$THide = array('rowid','fk_user');

	$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');

	$ATMdb=new TPDOdb;

	$r->liste($ATMdb, $sql, array(
		'limit'=>array(
			'nbLine'=>'30'
		)
		,'liste'=>array(
			'titre'=>$langs->trans('ListAsstLot')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messageNothing'=>"Il n'y a aucun ".$langs->trans('AssetLot')." à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
		,'title'=>array(
			'rowid'=>'ID'
			,'lot_number'=>'Numéro de lot'
			,'assetinlot'=>'Nb Equipement dans ce lot'
			,'productinlot'=>'Nb Produit dans ce lot'
		)
		,'link'=>array(
			'lot_number'=>'<a href="'.dol_buildpath('/asset/fiche_lot.php?id=@rowid@',1).'">@val@</a>'
		)
	));
		
	echo '<div class="tabsAction">';
	echo '<a class="butAction" href="fiche_lot.php?action=new">'.$langs->trans('CreateAssetLot').'</a>';
	echo '</div>';

	$ATMdb->close();

	llxFooter('');
	
}
