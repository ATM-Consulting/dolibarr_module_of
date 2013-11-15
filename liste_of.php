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
	
	if(isset($_REQUEST['fk_product'])){
		if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			
		$product = new Product($db);
		$result=$product->fetch($_REQUEST['fk_product']);	
			
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabEquipement2', $titre, 0, $picto);
	}
	
	$form=new TFormCore;

	$fields ="ofe.rowid, ofe.numero, ofe.ordre, ofe.date_lancement , ofe.date_besoin, ofe.status, ofe.fk_user"; 
	
	$assetOf=new TAssetOF;
	$r = new TSSRenderControler($assetOf);
	
	$sql="SELECT ".$fields."
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe
		  WHERE 1 ";
	
	$fk_soc=0;$fk_product=0;
	if(isset($_REQUEST['fk_soc'])) {$sql.=" AND e.fk_soc=".$_REQUEST['fk_soc']; $fk_soc=$_REQUEST['fk_soc'];}
	//if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	if($id_entity!=0) {
		$sql.= ' AND ofe.entity='.$id_entity;		
	}
	/*if(isset($_REQUEST['fk_product'])) {
		$sql.= ' AND ofel.fk_product='.$_REQUEST['fk_product'].' AND ofel.type = "TO_MAKE"';		
	}*/
	
	
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
			,'numero'=>'<a href="fiche_of.php?id=@rowid@">'.img_picto('','object_list.png','',0).' @val@</a>'
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
		/*,'eval'=>array(
			'ordre'=>'$assetOf->getOrdre(@val@)'
		)*/
	));
		
	echo '<div class="tabsAction">';
	echo '<a class="butAction" href="fiche_of.php?action=new'.((isset($_REQUEST['fk_product'])) ? '&fk_product='.$_REQUEST['fk_product'] : '' ).'">Créer un '.$langs->trans('OFAsset').'</a>';
	echo '</div>';

	$ATMdb->close();

	llxFooter('');
	
}
?>