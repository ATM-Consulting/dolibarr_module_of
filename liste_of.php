<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/commande/class/commande.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	dol_include_once("/core/class/html.formother.class.php");
	dol_include_once("/core/lib/company.lib.php");
	
	$langs->load('asset@asset');
	
	$action = __get('action');
	
	switch ($action) {
		case 'createOFCommande':

			$ATMdb = new TPDOdb;

			_createOFCommande($ATMdb, $_REQUEST['TProducts'], $_REQUEST['TQuantites'], $_REQUEST['fk_commande'], $_REQUEST['fk_soc'], isset($_REQUEST['subFormAlone']));
			_liste();
			break;
		
		default:
			_liste();		
			break;
	}	
	

function _createOFCommande($ATMdb, $TProduct, $TQuantites, $fk_commande, $fk_soc, $oneOF = false) {
/*
 * Créé des Of depuis un tableau de product
 */	
 
	global $db, $langs;

	if(!empty($TProduct)) {
			if($oneOF) {
					$assetOf = new TAssetOF;
					$assetOf->fk_commande = $fk_commande;
			}
			
			foreach($_REQUEST['TProducts'] as $fk_commandedet=>$v) {
				
				foreach($v as $fk_product=>$dummy) {
					if(!$oneOF) {
							$assetOf = new TAssetOF;
							$assetOf->fk_commande = $fk_commande;
					}
					
					if($assetOf->fk_commande > 0) {
						$com = new Commande($db);
						$com->fetch($assetOf->fk_commande);
						$assetOf->fk_project = $com->fk_project;
						if(!empty($com->date_livraison)) $assetOf->date_besoin = $com->date_livraison;
					}
					
					$assetOf->fk_soc = $fk_soc;
					$assetOf->addLine($ATMdb, $fk_product, 'TO_MAKE', $TQuantites[$fk_product], 0, '', 0, $fk_commandedet);
					$assetOf->save($ATMdb);
					
				}
			}
			
			setEventMessage($langs->trans('OFAsset')." créé(s) avec succès", 'mesgs');
			
	}
			
}

function _liste() {
	global $langs,$db,$user,$conf;
	
	$langs->load('asset@asset');
	
	llxHeader('',$langs->trans('ListOFAsset'),'','');
	getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?php echo $langs->trans('OFAssetDeleted'); ?></div><br>
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

	$sql="SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(ofel.qty) as nb_product_to_make, ofel.fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
		, ofe.status, ofe.fk_user, ofe.total_cost
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe 
		  LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'TO_MAKE')
		  LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ofel.fk_product
		  LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = ofe.fk_soc
		  WHERE ofe.entity=".$conf->entity
		  ;

	if($fk_soc>0) {$sql.=" AND ofe.fk_soc=".$fk_soc; }
	if($fk_product>0) {$sql.=" AND ofel.fk_product=".$fk_product;}
	if($fk_commande>0) {$sql.=" AND ofe.fk_commande=".$fk_commande;}
	//if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	$sql.=" GROUP BY ofe.rowid ";
	
	// TODO je me rappelle plus pourquoi j'ai fait cette merde mais ça fait planter le tri, donc à virer. 
	
	
	if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
	else $orderBy['ofe.date_cre']='DESC';
	
	/*if(isset($_REQUEST['fk_product'])) {
		$sql.= ' AND ofel.fk_product='.$_REQUEST['fk_product'].' AND ofel.type = "TO_MAKE"';		
	}*/
	
	$TMath=array();
	$THide = array('rowid','fk_user','fk_product','fk_soc');
	if(empty($user->rights->asset->of->price)){
		 $THide[] = 'total_cost';
	}
	else {
		$TMath['total_cost']='sum';
	}
	
	if(!empty($_REQUEST['fk_product'])) $TMath['nb_product_to_make']='sum';
	
	
	$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');

	$ATMdb=new TPDOdb;

	$r->liste($ATMdb, $sql, array(
		'limit'=>array(
			'nbLine'=>$conf->liste_limit
		)
		,'orderBy'=>$orderBy
		,'subQuery'=>array()
		,'link'=>array(
			'Utilisateur en charge'=>'<a href="'.DOL_URL_ROOT.'/user/card.php?id=@fk_user@">'.img_picto('','object_user.png','',0).' @val@</a>'
			,'numero'=>'<a href="fiche_of.php?id=@rowid@">'.img_picto('','object_list.png','',0).' @val@</a>'
			,'product'=>'<a href="'.DOL_URL_ROOT.'/product/card.php?id=@fk_product@">'.img_picto('','object_product.png','',0).' @val@</a>'
			,'client'=>'<a href="'.DOL_URL_ROOT.'/societe/soc.php?id=@fk_soc@">'.img_picto('','object_company.png','',0).' @val@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array(
			'date_lancement'=>'date'
			,'date_besoin'=>'date'
			,'total_cost'=>'money'
			,'nb_product_to_make'=>'number'
		)
		,'math'=>$TMath
		,'liste'=>array(
			'titre'=>$langs->trans('ListOFAsset')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messa geNothing'=>"Il n'y a aucun ".$langs->trans('OFAsset')." à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
		,'title'=>array(
			'numero'=>'Numéro'
			,'ordre'=>'Priorité'
			,'date_lancement'=>'Date du lancement'
			,'date_besoin'=>'Date du besoin'
			,'status'=>'Status'
			,'login'=>'Utilisateur en charge'
			,'product'=>'Produit'
			,'client'=>'Client'
			,'nb_product_to_make'=>'Nb produits à fabriquer'
			,'total_cost'=>'Coût'
		)
		,'eval'=>array(
			'ordre'=>'TAssetOF::ordre(@val@)'
			,'status'=>'TAssetOF::status(@val@)'
			,'product' => 'get_format_libelle_produit(@fk_product@)'
			,'client' => 'get_format_libelle_societe(@fk_soc@)'
		)
        ,'search'=>array(
            'numero'=>array('recherche'=>true, 'table'=>'ofe')
            ,'date_lancement'=>array('recherche'=>'calendars', 'table'=>'ofe')
            ,'date_besoin'=>array('recherche'=>'calendars', 'table'=>'ofe')
            ,'status'=>array('recherche'=>TAssetOF::$TStatus, 'table'=>'ofe')
        )
	));
	
	$form->end();
	
	// 
	// On n'affiche pas le bouton de création d'OF si on est sur la liste OF depuis l'onglet "OF" de la fiche commande
	if($fk_commande) {
				
		$commande=new Commande($db);
		$commande->fetch($fk_commande);	
				
		$r2 = new TSSRenderControler($assetOf);

		$sql = "SELECT c.rowid as fk_commandedet, p.rowid as rowid, p.ref as refProd, p.label as nomProd, c.qty as qteCommandee";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet c INNER JOIN ".MAIN_DB_PREFIX."product p";
		$sql.= " ON c.fk_product = p.rowid";
		$sql.= " WHERE c.fk_commande = ".$fk_commande;
		
		$resql = $db->query($sql);

		$num = $db->num_rows($resql);
		$limit = $conf->liste_limit;
	
		print_barre_liste($langs->trans('ListOrderProducts'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);
	
	
		$i = 0;
		
		$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');
		echo $form->hidden('fk_commande', __get('fk_commande',0,'int'));
		echo $form->hidden('action', 'createOFCommande');
		echo $form->hidden('fk_soc', $commande->socid);
		
		print '<table class="noborder" width="100%">';
	
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),"liste_of.php","ref","",$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),"liste_of.php","label", "", $param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Produits à ajouter à un OF"),"liste_of.php","","",$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Quantité à produire"),"liste_of.php","","",$param,'',$sortfield,$sortorder);
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
			print "<td>".$form->checkbox1('', 'TProducts['.$prod->fk_commandedet.']['.$prod->rowid.']', false);
			print "</td>";
			print "<td>";
			print $form->texte('','TQuantites['.$prod->rowid.']', $prod->qteCommandee,3,255);
			print "</td>";
			print "</tr>\n";
	
			$i++;
		}
	
		print "</table>";
		
		echo '</div>';
		
		echo '<p align="right">'.$form->btsubmit('Créer OFs', 'subForm').' '.$form->btsubmit('Créer un seul OF', 'subFormAlone').'</p>';
		$form->end();
		
		$db->free($resql);


	} else {
		                
		if(!empty($_REQUEST['fk_product'])) {
		    
            
            $sql="SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(ofel.qty) as nb_product_needed, ofel.fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
            , ofe.status, ofe.fk_user, ofe.total_cost
              FROM ".MAIN_DB_PREFIX."assetOf as ofe 
              LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'NEEDED')
              LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ofel.fk_product
              LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = ofe.fk_soc
              WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$_REQUEST['fk_product']." AND ofe.status!='CLOSE'";
              ;

            $sql.=" GROUP BY ofe.rowid ";
            
            if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
            else $orderBy['ofe.date_cre']='DESC';
            
            $TMath=array();
            $THide = array('rowid','fk_user','fk_product','fk_soc');
            if(empty($user->rights->asset->of->price)){
                 $THide[] = 'total_cost';
            }
            else {
                $TMath['total_cost']='sum';
            }
            
            if(!empty($_REQUEST['fk_product'])) $TMath['nb_product_needed']='sum';
            
            $l=new TListviewTBS('listeofproductneeded');
            print '<strong>Liste OF ayant besoin du produit</strong>';
            echo $l->render($ATMdb, $sql, array(
                'limit'=>array(
                    'nbLine'=>$conf->liste_limit
                )
                ,'orderBy'=>$orderBy
                ,'subQuery'=>array()
                ,'link'=>array(
                    'Utilisateur en charge'=>'<a href="'.DOL_URL_ROOT.'/user/card.php?id=@fk_user@">'.img_picto('','object_user.png','',0).' @val@</a>'
                    ,'numero'=>'<a href="fiche_of.php?id=@rowid@">'.img_picto('','object_list.png','',0).' @val@</a>'
                    ,'product'=>'<a href="'.DOL_URL_ROOT.'/product/card.php?id=@fk_product@">'.img_picto('','object_product.png','',0).' @val@</a>'
                    ,'client'=>'<a href="'.DOL_URL_ROOT.'/societe/soc.php?id=@fk_soc@">'.img_picto('','object_company.png','',0).' @val@</a>'
                )
                ,'translate'=>array()
                ,'hide'=>$THide
                ,'type'=>array(
                    'date_lancement'=>'date'
                    ,'date_besoin'=>'date'
                    ,'total_cost'=>'money'
                    ,'nb_product_needed'=>'number'
                )
                ,'math'=>$TMath
                ,'liste'=>array(
                    'titre'=>$langs->trans('ListOFAsset')
                    ,'image'=>img_picto('','title.png', '', 0)
                    ,'picto_precedent'=>img_picto('','back.png', '', 0)
                    ,'picto_suivant'=>img_picto('','next.png', '', 0)
                    ,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
                    ,'messa geNothing'=>"Il n'y a aucun ".$langs->trans('OFAsset')." à afficher"
                    ,'picto_search'=>img_picto('','search.png', '', 0)
                )
                ,'title'=>array(
                    'numero'=>'Numéro'
                    ,'ordre'=>'Priorité'
                    ,'date_lancement'=>'Date du lancement'
                    ,'date_besoin'=>'Date du besoin'
                    ,'status'=>'Status'
                    ,'login'=>'Utilisateur en charge'
                    ,'product'=>'Produit'
                    ,'client'=>'Client'
                    ,'nb_product_needed'=>'Nb produits nécessaire'
                    ,'total_cost'=>'Coût'
                )
                ,'eval'=>array(
                    'ordre'=>'TAssetOF::ordre(@val@)'
                    ,'status'=>'TAssetOF::status(@val@)'
                    ,'product' => 'get_format_libelle_produit(@fk_product@)'
                    ,'client' => 'get_format_libelle_societe(@fk_soc@)'
                )
            ));
            
            
		}
		        
		    
		
		echo '<div class="tabsAction">';
		
		if ($conf->nomenclature->enabled && !empty($_REQUEST['fk_product']))
		{
			dol_include_once('/core/class/html.form.class.php');
			dol_include_once('/asset/lib/asset.lib.php');
			dol_include_once('/nomenclature/class/nomenclature.class.php');
			
			$doliForm = new Form($db);
			echo $doliForm->selectarray('fk_nomenclature', TNomenclature::get($ATMdb, $_REQUEST['fk_product'], true));
			
			echo '<script type="text/javascript">
				$(function() {
				    var url_create_of = $("#bt_createOf").attr("href");
                    		$("#bt_createOf").attr("href","#");  
                        
					$("#bt_createOf").click(function() {
						var fk_nomenclature = $("select[name=fk_nomenclature]").val();
						var href = url_create_of + "&fk_nomenclature=" + fk_nomenclature;
						$(this).attr("href", href);
					});
				});
			</script>';
			
		}
		
		echo '<a id="bt_createOf" class="butAction" href="fiche_of.php?action=new'.((isset($_REQUEST['fk_product'])) ? '&fk_product='.$_REQUEST['fk_product'] : '' ).'">'.$langs->trans('CreateOFAsset').'</a>';
		echo '</div>';

	}

	$ATMdb->close();

	llxFooter('');
}

function get_format_libelle_produit($fk_product = null) {
	global $db;

	if (!empty($fk_product)) {
		$product = new Product($db);
		$product->fetch($fk_product);
	
		$product->ref.=' '.$product->label;
	
		return  $product->getNomUrl(1);
	} else {
		return 'Produit non défini.';
	}
}

function get_format_libelle_societe($fk_soc) {
	global $db;
	
    if($fk_soc>0) {
	$societe = new Societe($db);
	$societe->fetch($fk_soc);
	$url = $societe->getNomUrl(1);
	
	return $url;
	
    }
    
    return '';
}
