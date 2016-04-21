<?php
	require('config.php');
	
	ini_set('memory_limit','512M');
	
	dol_include_once('/of/class/ordre_fabrication_asset.class.php');
	dol_include_once('/product/class/product.class.php');
	dol_include_once('/commande/class/commande.class.php');
	
	if(!$user->rights->of->of->lire) accessforbidden();
	
	dol_include_once("/core/class/html.formother.class.php");
	dol_include_once("/core/lib/company.lib.php");
	
	$langs->load('of@of');
	$PDOdb = new TPDOdb;
	$action = __get('action');

	switch ($action) 
	{
		case 'createOFCommande':
			set_time_limit(0);
			_createOFCommande($PDOdb, $_REQUEST['TProducts'], $_REQUEST['TQuantites'], $_REQUEST['fk_commande'], $_REQUEST['fk_soc'], isset($_REQUEST['subFormAlone']));
			_liste($PDOdb);
			break;
		case 'printTicket':
			_printTicket($PDOdb);
		default:
			_liste($PDOdb);
			break;
	}
	
/*
 * Créé des Of depuis un tableau de product
 */	
function _createOFCommande(&$PDOdb, $TProduct, $TQuantites, $fk_commande, $fk_soc, $oneOF = false) 
{
	global $db, $langs;
	
	if(!empty($TProduct)) 
	{
		if($oneOF) 
		{
			$assetOf = new TAssetOF;
			$assetOf->fk_commande = $fk_commande;
		}

		foreach($TProduct as $fk_commandedet => $v) 
		{
			foreach($v as $fk_product=>$dummy) 
			{
				if(!$oneOF) 
				{
					$assetOf = new TAssetOF;
					$assetOf->fk_commande = $fk_commande;
				}
				
				if($assetOf->fk_commande > 0) 
				{
					$com = new Commande($db); //TODO on est pas censé toujours être sur la même commande ? AA 
					$com->fetch($assetOf->fk_commande);
					$assetOf->fk_project = $com->fk_project;
					if(!empty($com->date_livraison)) $assetOf->date_besoin = $com->date_livraison;
				}
				
/*				pre($TQuantites,true);
				pre($TProduct,true);exit;*/

				$qty = $TQuantites[$fk_commandedet];

//print "$fk_product x $qty<br />";
				$assetOf->fk_soc = $fk_soc;
				$assetOf->addLine($PDOdb, $fk_product, 'TO_MAKE', $qty, 0, '', 0, $fk_commandedet);
				$assetOf->save($PDOdb);
				
			}
		}
		
		setEventMessage($langs->trans('OFAsset')." créé(s) avec succès", 'mesgs');
	}
			
}

function _liste(&$PDOdb)
{
	global $langs,$db,$user,$conf;
	
	llxHeader('',$langs->trans('ListOFAsset'),'','');
	//getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?php echo $langs->trans('OFAssetDeleted'); ?></div><br>
		<?php
	}
	
	$fk_soc=__get('fk_soc',0,'integer');
	$fk_product=__get('fk_product',0,'integer');
	$fk_commande=__get('fk_commande',0,'integer');

	if($fk_product > 0)
	{
		dol_include_once('/core/lib/product.lib.php');

		$product = new Product($db);
		$result=$product->fetch($fk_product);	

		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabOF2', $titre, 0, $picto);
	} 
	elseif($fk_commande > 0) 
	{
		dol_include_once("/core/lib/order.lib.php");
		
		$commande = new Commande($db);
		$result=$commande->fetch($fk_commande);
		
		$head=commande_prepare_head($commande, $user);
		$titre=$langs->trans("CustomerOrder".$product->type);
		dol_fiche_head($head, 'tabOF3', $titre, 0, "order");
	}
	
	$form=new TFormCore;
	$assetOf=new TAssetOF;
	
	$r = new TSSRenderControler($assetOf);

	$sql="SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(ofel.qty) as nb_product_to_make
		, GROUP_CONCAT(DISTINCT ofel.fk_product SEPARATOR ',') as fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin, ofe.fk_commande,ofe.fk_project
		, ofe.status, ofe.fk_user,ofe.total_estimated_cost, ofe.total_cost, '' AS printTicket
		  FROM ".MAIN_DB_PREFIX."assetOf as ofe 
		  LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'TO_MAKE')
		  LEFT JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = ofel.fk_product)
		  LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = ofe.fk_soc)
		  WHERE ofe.entity=".$conf->entity;

	if($fk_soc>0) $sql.=" AND ofe.fk_soc=".$fk_soc; 
	if($fk_product>0) $sql.=" AND ofel.fk_product=".$fk_product;
	if($fk_commande>0) $sql.=" AND ofe.fk_commande=".$fk_commande;
	
	$sql.=" GROUP BY ofe.rowid ";
	
	// TODO je me rappelle plus pourquoi j'ai fait cette merde mais ça fait planter le tri, donc à virer. 
	/*if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
	else $orderBy['ofe.date_cre']='DESC';*/
	
	$TMath=array();
	$THide = array('rowid','fk_user','fk_product','fk_soc');
	if($fk_commande>0)  $THide[] = 'fk_commande';

	if ($conf->global->OF_NB_TICKET_PER_PAGE == -1) $THide[] = 'printTicket';
	
	if(empty($user->rights->of->of->price)) {
		$THide[] = 'total_cost';
		$THide[] = 'total_estimated_cost';
	}
	else {
		
		$TMath['total_estimated_cost']='sum';
		$TMath['total_cost']='sum';
	}
	
	if(!empty($fk_product)) $TMath['nb_product_to_make']='sum';
	
	$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');
	
	echo $form->hidden('action', '');
	if ($fk_commande > 0) echo $form->hidden('fk_commande', $fk_commande);
	if($fk_product > 0) echo $form->hidden('fk_product', $fk_product); // permet de garder le filtre produit quand on est sur l'onglet OF d'une fiche produit
	
	$r->liste($PDOdb, $sql, array(
		'limit'=>array(
			'nbLine'=>$conf->liste_limit
		)
		,'orderBy'=>$orderBy
		,'subQuery'=>array()
		,'link'=>array(
			'Utilisateur en charge'=>'<a href="'.dol_buildpath('/user/card.php?id=@fk_user@', 2).'">'.img_picto('','object_user.png','',0).' @val@</a>'
			,'numero'=>'<a href="'.dol_buildpath('/of/fiche_of.php?id=@rowid@"', 2).'>'.img_picto('','object_list.png','',0).' @val@</a>'
			,'printTicket'=>'<input style=width:40px;"" type="number" value="'.((int) $conf->global->OF_NB_TICKET_PER_PAGE).'" name="printTicket[@rowid@]" min="0" />'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array(
			'date_lancement'=>'date'
			,'date_besoin'=>'date'
			,'total_cost'=>'money'
			,'total_estimated_cost'=>'money'
			,'nb_product_to_make'=>'number'
		)
		,'math'=>$TMath
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
			,'fk_commande'=>'Commande client'
			,'ordre'=>'Priorité'
			,'date_lancement'=>'Date du lancement'
			,'date_besoin'=>'Date du besoin'
			,'status'=>'Status'
			,'login'=>'Utilisateur en charge'
			,'product'=>'Produit'
			,'client'=>'Client'
			,'nb_product_to_make'=>'Nb produits à fabriquer'
			,'total_cost'=>'Coût réel'
			,'total_estimated_cost'=>'Coût prévu'
			,'printTicket' => 'impression<br />étiquette'
			,'fk_project'=>'Projet'
		)
		,'orderBy'=>array(
			'rowid'=>'DESC'
		)
		,'eval'=>array(
			'ordre'=>'TAssetOF::ordre(@val@)'
			,'status'=>'TAssetOF::status(@val@)'
			,'product' => 'get_format_libelle_produit("@fk_product@")'
			,'client' => 'get_format_libelle_societe(@fk_soc@)'
			,'fk_commande'=>'get_format_libelle_commande(@fk_commande@)'
			,'fk_project'=>'get_format_libelle_projet(@fk_project@)'

		)
        ,'search'=>array(
            'numero'=>array('recherche'=>true, 'table'=>'ofe')
            ,'date_lancement'=>array('recherche'=>'calendars', 'table'=>'ofe')
            ,'date_besoin'=>array('recherche'=>'calendars', 'table'=>'ofe')
            ,'status'=>array('recherche'=>TAssetOF::$TStatus, 'table'=>'ofe')
        )
	));
	
	if ($conf->global->OF_NB_TICKET_PER_PAGE != -1) {
		echo '<p align="right"><input class="button" type="button" onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'printTicket\');  $(this).closest(\'form\').submit(); " name="print" value="'.$langs->trans('ofPrintTicket').'" /></p>';
	}

	$form->end();
	
	// On n'affiche pas le bouton de création d'OF si on est sur la liste OF depuis l'onglet "OF" de la fiche commande
	if($fk_commande) 
	{
		$commande=new Commande($db);
		$commande->fetch($fk_commande);	
		
		$r2 = new TSSRenderControler($assetOf);

		$sql = "SELECT c.rowid as fk_commandedet, p.rowid as rowid, p.ref as refProd, p.label as nomProd, c.qty as qteCommandee, c.description, c.product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet c LEFT JOIN ".MAIN_DB_PREFIX."product p";
		$sql.= " ON (c.fk_product = p.rowid)";
		$sql.= " WHERE c.product_type IN (0,9) AND  c.fk_commande = ".$fk_commande;
		
		$resql = $db->query($sql);
//var_dump($db);
		$num = $db->num_rows($resql);
	
		print_barre_liste($langs->trans('ListOrderProducts'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);
	
		$i = 0;
		
		$form=new TFormCore($_SERVER['PHP_SELF'], 'formMakeOk', 'post');
		echo $form->hidden('fk_commande', __get('fk_commande',0,'int'));
		echo $form->hidden('action', 'createOFCommande');
		echo $form->hidden('fk_soc', $commande->socid);
		echo $form->hidden('token', $_SESSION['newtoken']); 
		
		print '<table class="noborder" width="100%">';
	
		print '<tr class="liste_titre">';
		print_liste_field_titre("#");
		print_liste_field_titre($langs->trans("Ref"),"liste_of.php","ref","",$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),"liste_of.php","label", "", $param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Quantité à produire"),"liste_of.php","","",$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Produits à ajouter à un OF"),"liste_of.php","","",$param,'',$sortfield,$sortorder);
		print "</tr>\n";
		$var=1;
		
		$bc = array(1=>'class="pair"',-1=>'class="impair"');

		while ($prod = $db->fetch_object($resql))
		{
			$var=!$var;
			//print "<tr ".$bc[$var].">";
			

			if($prod->product_type == 9) {
				 print "<tr>";
				print "<td>&nbsp;</td>";
                                 print "<td colspan=\"4\"><strong>";
                                 print $prod->description;
                                 print '</strong></td>';
			}
			else if(empty($prod->rowid)) {
				// ligne libre
				print "<tr>";
				  print "<td>&nbsp;</td>";

				print "<td colspan=\"4\">";
				print $prod->description;
				print '</td>';
								
			}
			else {
				
				print "<tr ".$bc[$var].">";
			       print "<td>".($i+1)."</td>";

				print "<td>";
				$p_static = new Product($db);
				$p_static->ref = $prod->refProd;
				$p_static->id = $prod->rowid;
				print $p_static->getNomUrl(1);
				print "</td>\n";
				print '<td>';
				print $prod->nomProd;
				print '</td>';
			    	        print "<td>";
        	                print $form->texte('','TQuantites['.$prod->fk_commandedet.']', $prod->qteCommandee,3,255);
                        	print "</td>";
	        
				 print "<td>".$form->checkbox1('', 'TProducts['.$prod->fk_commandedet.']['.(int)$prod->rowid.']', false,true,'','checkOF' );
	                        print "</td>";
            
			                print "</tr>\n";
				$i++;


			}
	
		}
	
		print '<tr class="liste_titre">';
		echo '<th class="liste_titre" colspan="2">&nbsp;</th><th class="liste_titre">&nbsp;</th><th class="liste_titre">&nbsp;</th>
		<th class="liste_titre"><input type="checkbox" id="checkall" checked="checked" value="1"></th>
		';
		print '</tr>';
	
		print "</table>";
	
		?><script type="text/javascript">
			$('input#checkall').change(function() {
				
				$('input.checkOF').prop('checked',$(this).is(':checked'));	
				
			});
			
		</script>
		
		<?php
		
		echo '<p align="right">'.$form->btsubmit('Créer OFs', 'subForm')
		.' '.$form->btsubmit('Créer un seul OF', 'subFormAlone').'</p>';
		
		$form->end();
		
		echo '</div>';
		
		
		$db->free($resql);


	} 
	else 
	{
		if(!empty($fk_product)) 
		{
            $sql="SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(IF(ofel.qty>0,ofel.qty,ofel.qty_needed) ) as nb_product_needed, ofel.fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
            , ofe.status, ofe.fk_user, ofe.total_cost
              FROM ".MAIN_DB_PREFIX."assetOf as ofe 
              LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'NEEDED')
              LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ofel.fk_product
              LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = ofe.fk_soc
              WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$fk_product." AND ofe.status!='CLOSE'";
			  
            $sql.=" GROUP BY ofe.rowid ";
            
            if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
            else $orderBy['ofe.date_cre']='DESC';
            
            $TMath=array();
            $THide = array('rowid','fk_user','fk_product','fk_soc');
            if(empty($user->rights->asset->of->price)) $THide[] = 'total_cost';
            else $TMath['total_cost']='sum';
            
            $TMath['nb_product_needed']='sum';
            
            $l=new TListviewTBS('listeofproductneeded');
            echo $langs->trans('ofListProductNeeded');
            echo $l->render($PDOdb, $sql, array(
                'limit'=>array(
                    'nbLine'=>$conf->liste_limit
                )
                ,'orderBy'=>$orderBy
                ,'subQuery'=>array()
                ,'link'=>array(
                    'Utilisateur en charge'=>'<a href="'.dol_buildpath('/user/card.php?id=@fk_user@', 2).'">'.img_picto('','object_user.png','',0).' @val@</a>'
                    ,'numero'=>'<a href="'.dol_buildpath('/of/fiche_of.php?id=@rowid@', 2).'">'.img_picto('','object_list.png','',0).' @val@</a>'
                    ,'product'=>'<a href="'.dol_buildpath('/product/card.php?id=@fk_product@', 2).'">'.img_picto('','object_product.png','',0).' @val@</a>'
                    ,'client'=>'<a href="'.dol_buildpath('/societe/soc.php?id=@fk_soc@', 2).'">'.img_picto('','object_company.png','',0).' @val@</a>'
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
		echo '<a id="bt_createOf" class="butAction" href="fiche_of.php?action=new'.((!empty($fk_product)) ? '&fk_product='.$fk_product : '' ).'">'.$langs->trans('CreateOFAsset').'</a>';
		if ($conf->nomenclature->enabled && !empty($fk_product))
		{
			dol_include_once('/core/class/html.form.class.php');
			dol_include_once('/asset/lib/asset.lib.php');
			dol_include_once('/nomenclature/class/nomenclature.class.php');
			
			$doliForm = new Form($db);
			echo $doliForm->selectarray('fk_nomenclature', TNomenclature::get($PDOdb, $fk_product, true));
			
			echo '<script type="text/javascript">

				    var url_create_of = $("#bt_createOf").attr("href");
	                   	    $("#bt_createOf").attr("href","#");  
                        
					$("#bt_createOf").click(function() {
						var fk_nomenclature = $("select[name=fk_nomenclature]").val();
						var href = url_create_of + "&fk_nomenclature=" + fk_nomenclature;
						$(this).attr("href", href);
					});
			</script>';
			
		}
		
		echo '</div>';

	}

	$PDOdb->close();
	llxFooter('');
}

function get_format_libelle_produit($fk_product = null) 
{
	global $db,$langs;

	if (!empty($fk_product)) 
	{
	
		$TId = explode(',',$fk_product);
		$nb_product = count($TId);
		
		$product = new Product($db);
		$product->fetch($TId[0]);
	
		$product->ref.=' '.$product->label;
	
		$res = $product->getNomUrl(1).($nb_product>1 ? ' + '.($nb_product-1).' '.$langs->trans('products') : '');
		return $res;
	} 
	else 
	{
		return 'Produit non défini.';
	}
}

function get_format_libelle_societe($fk_soc) 
{
	global $db;
	
    if($fk_soc>0) 
    {
		$societe = new Societe($db);
		$societe->fetch($fk_soc);
		$url = $societe->getNomUrl(1);
		
		return $url;
    }
    
    return '';
}

function get_format_libelle_commande($fk) 
{
        global $db;

    if($fk>0) 
    {
                $o = new Commande($db);
                if($o->fetch($fk)>0) return $o->getNomUrl(1);
		else return $fk;
    }
   
    return '';
}
function get_format_libelle_projet($fk) {
    global $db;

    if($fk>0) 
    {
		dol_include_once('/projet/class/project.class.php');
                $o = new Project($db);
                if($o->fetch($fk)>0) return $o->getNomUrl(1);
                else return $fk;
    }
   
    return '';
}


function _printTicket(&$PDOdb)
{
	global $db,$conf,$langs;
	
	$dirName = 'OF_TICKET('.date("Y_m_d").')';
	$dir = DOL_DATA_ROOT.'/of/'.$dirName.'/';
	$fileName = date('YmdHis').'_ETIQUETTE';
	
	$TPrintTicket = GETPOST('printTicket', 'array');
	$TInfoEtiquette = _genInfoEtiquette($db, $PDOdb, $TPrintTicket);
	//var_dump($TInfoEtiquette);exit;
	@mkdir($dir, 0777, true);
	
	if(defined('TEMPLATE_OF_ETIQUETTE')) $template = TEMPLATE_OF_ETIQUETTE;
	else if($conf->global->DEFAULT_ETIQUETTES == 2){
		$template = "etiquette_custom.html";
	}else{
		$template = "etiquette.html";
	}

	$TBS=new TTemplateTBS();
	$file_path = $TBS->render(dol_buildpath('/of/exempleTemplate/'.$template)
		,array(
			'TInfoEtiquette'=>$TInfoEtiquette
		)
		,array(
			'date'=>date("d/m/Y")
			,'margin_top' =>  intval($conf->global->DEFINE_MARGIN_TOP)
			, 'margin_left_impair' => intval($conf->global->DEFINE_MARGIN_LEFT)
			, 'width' => intval($conf->global->DEFINE_WIDTH_DIV)
			, 'height' => intval($conf->global->DEFINE_HEIGHT_DIV)
			, 'margin_right_pair' =>intval($conf->global->DEFINE_MARGIN_RIGHT)
			, 'margin_top_cell' =>intval($conf->global->DEFINE_MARGIN_TOP_CELL)
			)
		,array()
		,array(
			'outFile'=>$dir.$fileName.".html"
			,'convertToPDF'=>true
		)
		
	);
	
	header("Location: ".dol_buildpath("/document.php?modulepart=of&entity=1&file=".$dirName."/".$fileName.".pdf", 1));
	exit;
}

function _genInfoEtiquette(&$db, &$PDOdb, &$TPrintTicket)
{
	global $conf; 
	
	$TInfoEtiquette = array();
	if (empty($TPrintTicket)) return $TInfoEtiquette;
	
	dol_include_once('/commande/class/commande.class.php');
	
	$assetOf = new TAssetOF;
	$cmd = new Commande($db);
	$product = new Product($db);
	$pos = 1;
	$cpt=0;
	foreach ($TPrintTicket as $fk_assetOf => $qty)
	{
		if ($qty <= 0) continue;
		
		$load = $assetOf->load($PDOdb, $fk_assetOf);
		
		if ($load === true)
		{
			$cmd->fetch($assetOf->fk_commande);
			
			foreach ($assetOf->TAssetOFLine as &$assetOfLine)
			{
				
				if ($assetOfLine->type == 'TO_MAKE' && $product->fetch($assetOfLine->fk_product) > 0)
				{
					for ($i = 0; $i < $qty; $i++)
					{
						$cpt++;
						if (($cpt%2)==0)$div='pair';
						else $div='impair';
						$TInfoEtiquette[] = array(
							'numOf' => $assetOf->numero
							,'float' => $div
							,'refCmd' => $cmd->ref
							,'refCliCmd' => $cmd->ref_client
							,'refProd' => $product->ref
							,'qty_to_print' => $qty
							,'qty_to_make' => $assetOfLine->qty
							,'label' => wordwrap(preg_replace('/\s\s+/', ' ', $product->label), 20, $conf->global->DEFAULT_ETIQUETTES == 2?"\n":"</br>")
							,'pos' => ceil($pos/8)		
						);
					
						//var_dump($TInfoEtiquette);exit;
						$pos++;
						//var_dump($TInfoEtiquette);
					}
				}
			}
		}
		
	}//exit;
	
	return $TInfoEtiquette;
}
