<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	
	_liste();

function _liste() {
	global $langs,$db,$user,$conf;
	
	$langs->load('asset@asset');
	
	llxHeader('',$langs->trans('ListOFAsset'),'','');
	getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?= $langs->trans('OFAssetDeleted'); ?></div><br>
		<?
	}
	
	$fk_soc=__get('fk_soc',0,'integer');
	$fk_product=__get('fk_product',0,'integer');
	$fk_commande=__get('fk_commande',0,'integer');
	
	
	if($fk_product>0){
		if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		
		//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		dol_include_once("/product/class/product.class.php");
			
		$product = new Product($db);
		$result=$product->fetch($_REQUEST['fk_product']);	
			
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabOF2', $titre, 0, $picto);
		
	} elseif($fk_commande > 0) {
		if(is_file(dol_buildpath("/lib/order.lib.php", 1))) dol_include_once("/lib/order.lib.php");
		else dol_include_once("/core/lib/order.lib.php");		
		
		dol_include_once("/commande/class/commande.class.php");
		
		$commande = new Commande($db);
		$result=$commande->fetch($_REQUEST['fk_commande']);	
			
		$head=commande_prepare_head($commande, $user);
		$titre=$langs->trans("CustomerOrder".$product->type);
		dol_fiche_head($head, 'tabOF3', $titre, 0, "order");
		
	}
	
	$form=new TFormCore;

	$assetOf=new TAssetOF;
	$r = new TSSRenderControler($assetOf);
	
	$sql="SELECT ofe.rowid, ofe.numero, ofe.ordre, ofe.date_lancement , ofe.date_besoin, ofe.status, ofe.fk_user
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid) 
		  
	WHERE ofe.entity=".$conf->entity." 
	";
	
	
	if($fk_soc>0) {$sql.=" AND ofe.fk_soc=".$fk_soc; }
	if($fk_product>0) {$sql.=" AND ofel.fk_product=".$fk_product;}
	if($fk_commande>0) {$sql.=" AND ofe.fk_commande=".$fk_commande;}
	//if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	$sql.=" GROUP BY ofe.rowid ";
	
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
			'titre'=>$langs->trans('ListOFAsset')
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
		,'eval'=>array(
			'ordre'=>'TAssetOF::ordre(@val@)'
			,'status'=>'TAssetOF::status(@val@)'
		)
	));
	
	// 
	// On n'affiche pas le bouton de création d'OF si on est sur la liste OF depuis l'onglet "OF" de la fiche commande
	if($fk_commande) {
				
		$r2 = new TSSRenderControler($assetOf);
		
		$sql = "SELECT DISTINCT p.ref as refProd, p.label as nomProd";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet c INNER JOIN ".MAIN_DB_PREFIX."product p";
		$sql.= " ON c.fk_product = p.rowid";
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line a";
		//$sql.= " ON a.fk_product = p.rowid";
		$sql.= " WHERE c.fk_commande = ".$fk_commande;
		//$sql.= " AND a.type = 'TO_MAKE'";
		//$sql.= " AND numLigneOf NOT NULL";
		
		$r2->liste($ATMdb, $sql, array(
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
				'titre'=>$langs->trans('ListOrderProducts')
				,'image'=>img_picto('','title.png', '', 0)
				,'picto_precedent'=>img_picto('','back.png', '', 0)
				,'picto_suivant'=>img_picto('','next.png', '', 0)
				,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
				,'messageNothing'=>"Il n'y a aucun ".$langs->trans('Product')." à afficher"
				,'picto_search'=>img_picto('','search.png', '', 0)
			)
			,'title'=>array(
				'refProd'=>$langs->trans('Ref')
				,'nomProd'=>$langs->trans('Label')
				,'numLigneOf'=>'Num ligne OF'
			)
			,'eval'=>array(
				'ordre'=>'TAssetOF::ordre(@val@)'
				,'status'=>'TAssetOF::status(@val@)'
			)
		));		
		
	} else {
		
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="fiche_of.php?action=new'.((isset($_REQUEST['fk_product'])) ? '&fk_product='.$_REQUEST['fk_product'] : '' ).'">'.$langs->trans('CreateOFAsset').'</a>';
		echo '</div>';
		
	}

	$ATMdb->close();

	llxFooter('');
	
}
