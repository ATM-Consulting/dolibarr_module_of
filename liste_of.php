<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	
	_liste($user->entity);

function _liste($id_entity) {
	global $langs,$db,$user;
	
	$langs->load('asset@asset');
	
	llxHeader('',$langs->trans('ListOFAsset'),'','');
	getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?= $langs->trans('OFAssetDeleted'); ?></div><br>
		<?
	}

	$form=new TFormCore;

	$fields ="ofe.rowid, ofe.numero, ofe.ordre, ofe.date_lancement , ofe.date_besoin, ofe.status, u.login ,ofe.fk_user"; 
	
	$assetOf=new TAssetOF;
	$r = new TSSRenderControler($assetOf);
	
	$sql="SELECT ".$fields."
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe 
		  	LEFT JOIN ".MAIN_DB_PREFIX."user as u ON (ofe.fk_user=u.rowid)
		  WHERE 1 ";
			  
	$fk_soc=0;$fk_product=0;
	if(isset($_REQUEST['fk_soc'])) {$sql.=" AND e.fk_soc=".$_REQUEST['fk_soc']; $fk_soc=$_REQUEST['fk_soc'];}
	if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	if($id_entity!=0) {
		$sql.= ' AND ofe.entity='.$id_entity;		
	}
	
	
	$THide = array('rowid','fk_user');
	
	$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');
	
	$ATMdb=new TPDOdb;
	
	$r->liste($ATMdb, $sql, array(
		'limit'=>array(
			'nbLine'=>'30'
		)
		,'subQuery'=>array()
		,'link'=>array(
			'Utilisateur en charge'=>'<a href="'.DOL_URL_ROOT.'/user/fiche.php?id=@fk_user@">'.img_picto('','object_user.png','',0).' @val@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array(
			'date_lancement'=>'date'
			,'date_besoin'=>'date'
		)
		,'liste'=>array(
			'titre'=>'Liste des '.$langs->trans('OFAsset')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messageNothing'=>"Il n'y a aucun ".$langs->trans('OFAsset')." à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
		,'title'=>array(
			'numero'=>'Numéro'
			,'ordre'=>'Priorité'
			,'date_lancement'=>'Date du lancement'
			,'date_besoin'=>'Date du besoin'
			,'status'=>'Status'
			,'login'=>'Utilisateur en charge'
		)
		/*,'search'=>array(
			'serial_number'=>true
			,'nom'=>array('recherche'=>true, 'table'=>'s')
			,'label'=>array('recherche'=>true, 'table'=>'')
		)*/
	));
		
	echo '<div class="tabsAction">';
	echo '<a class="butAction" href="fiche.php?action=new">Créer un '.$langs->trans('OFAsset').'</a>';
	echo '</div>';

	$ATMdb->close();

	llxFooter('');
	
}
?>