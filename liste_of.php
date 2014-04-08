<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	
	$action = $_REQUEST['actionATM'];
	
	switch ($action) {
		case 'createOFCommande':

			$ATMdb = new TPDOdb;
			
			if(count($_REQUEST['TProducts']) != 0) {
				
				foreach($_REQUEST['TProducts'] as $k=>$v) {
					foreach($v as $fk_product=>$onSenFout) {
	
						_createOFCommande($ATMdb, $fk_product, $_REQUEST['fk_commande']);
						
					}
				}
				
				//setEventMessage($langs->trans('AssetOF')." créés avec succès", 'mesgs');
				
			}
			
			_liste();
			break;
		
		default:
			_liste();		
			break;
	}	
	

function _createOFCommande($ATMdb, $fk_product, $id_commande) {
	
	$assetOf = new TAssetOF;
	$assetOf->fk_commande = $id_commande;
	//function addLine(&$ATMdb, $fk_product, $type, $quantite=1,$fk_assetOf_line_parent=0){
	$assetOf->addLine($ATMdb, $fk_product, 'TO_MAKE');
	$assetOf->save($ATMdb);
	
}

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


	if($fk_product > 0){
		if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		
		//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		dol_include_once("/product/class/product.class.php");

		$product = new Product($db);
		$result=$product->fetch($fk_product);	

		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabOF2', $titre, 0, $picto);

	} elseif($fk_commande > 0) {
		if(is_file(dol_buildpath("/lib/order.lib.php", 1))) dol_include_once("/lib/order.lib.php");
		else dol_include_once("/core/lib/order.lib.php");		

		dol_include_once("/commande/class/commande.class.php");

		$commande = new Commande($db);
		$result=$commande->fetch($fk_commande);	

		$head=commande_prepare_head($commande, $user);
		$titre=$langs->trans("CustomerOrder".$product->type);
		dol_fiche_head($head, 'tabOF3', $titre, 0, "order");

	}
	
	$form=new TFormCore;

	$assetOf=new TAssetOF;
	$r = new TSSRenderControler($assetOf);

	$sql="SELECT ofe.rowid, ofe.numero, ofe.ordre, ofe.date_lancement , ofe.date_besoin, ofe.status, ofe.fk_user
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid) 
		  WHERE ofe.entity=".$conf->entity;

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

		$sql = "SELECT p.rowid as rowid, p.ref as refProd, p.label as nomProd";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet c INNER JOIN ".MAIN_DB_PREFIX."product p";
		$sql.= " ON c.fk_product = p.rowid";
		$sql.= " WHERE c.fk_commande = ".$fk_commande;
		
		$resql = $db->query($sql);

		$num = $db->num_rows($resql);
		$limit = $conf->liste_limit;
	
		print_barre_liste($langs->trans('ListOrderProducts'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);
	
	
		$i = 0;
		
		print '<form name="formAddProductsToOF" method="POST" action="'.dol_buildpath('/asset/liste_of.php', 2).'">';
		
		print '<input type="hidden" name=fk_commande value="'.$_REQUEST['fk_commande'].'" />';
		
		print '<input type="hidden" name=actionATM value="createOFCommande" />';
		
		print '<table class="noborder" width="100%">';
	
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),"liste_of.php","ref","",$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),"liste_of.php","label", "", $param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Produits à ajouter à un OF"),"liste_of.php","","",$param,'',$sortfield,$sortorder);
		print "</tr>\n";
		$var=True;
		
		while ($i < min($num,$limit))
		{
			$prod = $db->fetch_object($resql);
			//$var=!$var;
			//print "<tr ".$bc[$var].">";
			print "<tr>";
			print "<td>";
			print $prod->refProd;
			print "</td>\n";
			print '<td>';
			print $prod->nomProd;
			print '</td>';
			print "<td>";
			?>
				<input type="checkbox" name="TProducts[<?=$i?>][<?=$prod->rowid?>]" />
			<?
			
			print "</td>";
			print "</tr>\n";
	
			$i++;
		}
	
		print "</table>";
		
		echo '</div>';
		
		print '<input class="butAction" type="SUBMIT" name="subForm" value="Créer OFs" style="float:right" />';
		
		print "</form>";
		
		$db->free($resql);


	} else {
		
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="fiche_of.php?action=new'.((isset($_REQUEST['fk_product'])) ? '&fk_product='.$_REQUEST['fk_product'] : '' ).'">'.$langs->trans('CreateOFAsset').'</a>';
		echo '</div>';

	}

	$ATMdb->close();

	llxFooter('');
}
