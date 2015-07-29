<?php
	require('config.php');
	require('./class/asset.class.php');
	
	if(!$user->rights->asset->all->lire) accessforbidden();
	
	dol_include_once("/core/class/html.formother.class.php");
	dol_include_once("/core/lib/company.lib.php");
	dol_include_once('/core/lib/product.lib.php');
	dol_include_once('/product/class/product.class.php');
	
	_liste($user->entity);

function get_measuring_units_string($fk_asset,$unite){
            
        $PDOdb = new TPDOdb;    
        
        $asset = new TAsset;
        $asset->load($PDOdb, $fk_asset,false);
        $asset->assetType->load($PDOdb, $asset->fk_asset_type,false);
        
        if($asset->gestion_stock != 'UNIT'){
            return measuring_units_string($unite,$asset->assetType->measuring_units);
        }
        else{
            return 'unité(s)';
        }
        
        $PDOdb->close();
}

function _liste($id_entity) {
	global $langs,$db,$user,$ASSET_LINK_ON_FIELD,$conf;
	
	$ATMdb=new TPDOdb;
	
	$langs->load('other');
	$langs->load('asset@asset');
	
	llxHeader('',$langs->trans('ListAsset'),'','');
	getStandartJS();
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error"><?php echo  $langs->trans('AssetDeleted'); ?></div><br>
		<?
	}
	
	if(isset($_REQUEST['fk_soc'])) {
		$soc = new Societe($db);
		$soc->id = $_REQUEST['fk_soc'];
		$soc->fetch($_REQUEST['fk_soc']);
		$soc->info($_REQUEST['fk_soc']);
		
		
		$head = societe_prepare_head($soc);
		dol_fiche_head($head, 'tabEquipement2', $langs->trans("ThirdParty"),0,'company');
		
	}
	elseif(isset($_REQUEST['fk_product'])) {
		if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		else require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			
		$product = new Product($db);
		$result=$product->fetch($_REQUEST['fk_product']);
		
		$sql = "SELECT type_asset FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$product->id;
		$ATMdb->Execute($sql);
		$ATMdb->Get_line();
		$fk_asset_type = $ATMdb->Get_field('type_asset');
						
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabEquipement1', $titre, 0, $picto);
		
	}
	
	else{
		//print load_fiche_titre('Equipements');
	}


	$form=new TFormCore;
	
	if(defined('ASSET_LIST_FIELDS')){
		$fields = ASSET_LIST_FIELDS;
	}	
	else{
		$fields ="e.rowid as 'ID',e.serial_number, e.lot_number,p.rowid as 'fk_product',p.label, e.contenancereel_value as 'contenance', e.contenancereel_units as 'unite', e.date_cre as 'Création'";
	} 
	
	$asset=new TAsset;
	$r = new TSSRenderControler($asset);
	
	$sql="SELECT ".$fields."
		  FROM ((llx_asset e LEFT OUTER JOIN llx_product p ON (e.fk_product=p.rowid))
				LEFT OUTER JOIN ".MAIN_DB_PREFIX."societe s ON (e.fk_soc=s.rowid))";
	
	if($conf->clinomadic->enabled && isset($_REQUEST['pret']) && $_REQUEST['pret'] == 1 ){
		$sql .= " WHERE etat = 2"; //prêté
		$sql = "SELECT e.rowid as 'ID', e.serial_number, p.rowid as 'fk_product', p.label, s.rowid as 'fk_soc', s.nom,
				e.date_deb_pret as 'Date debut pret', e.date_fin_pret as 'Date fin pret'
				FROM ((llx_asset e LEFT OUTER JOIN llx_product p ON (e.fk_product=p.rowid))
				LEFT OUTER JOIN ".MAIN_DB_PREFIX."societe s ON (e.fk_societe_localisation=s.rowid))
				WHERE etat = 2";
	}
	else{
		$sql .= " WHERE 1";
	}
			  
	$fk_soc=0;$fk_product=0;
	if(isset($_REQUEST['fk_soc'])) {$sql.=" AND e.fk_soc=".$_REQUEST['fk_soc']; $fk_soc=$_REQUEST['fk_soc'];}
	if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	if($fk_soc==0 && $fk_product==0 && $id_entity!=0) {
		$sql.= ' AND e.entity='.$id_entity;		
	}	
	if(isset($_REQUEST['no_serial'])) {
		$sql.=" AND serial_number='' OR serial_number = 'ErrorBadMask'";		
	}
	
	if(dolibarr_get_const($db, "ASSET_LIST_BY_ROWID_DESC")) $sql.=" ORDER BY e.rowid DESC";
	
	$THide = array('fk_soc','fk_product');
	if (empty($conf->global->USE_LOT_IN_OF))
	{
		$THide[] = 'lot_number';
	}
	
	if(isset($_REQUEST['fk_product'])) {
		$THide[] = 'Produit,ID';
	}
	
	//echo $sql;
	
	
	
	$form=new TFormCore($_SERVER['PHP_SELF'], 'formDossier', 'GET');

	$r->liste($ATMdb, $sql, array(
		'limit'=>array(
			'nbLine'=>$conf->liste_limit
		)
		,'subQuery'=>array()
		,'link'=>array(
			'ID'=>'<a href="'.dol_buildpath('asset/fiche.php?id=@val@&action=view', 2).'">@val@</a>'
			,'nom'=>'<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid=@fk_soc@">'.img_picto('','object_company.png','',0).' @val@</a>'
			,'serial_number'=>'<a href="fiche.php?id=@ID@">@val@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array('Date garantie'=>'date','Date dernière intervention'=>'date', 'Date livraison'=>'date', 'Création'=>'date','Date fin pret'=>'date','Date debut pret'=>'date')
		,'liste'=>array(
			'titre'=> ($conf->clinomadic->enabled && isset($_REQUEST['pret']) && $_REQUEST['pret'] == 1 ) ?  'Liste des '.$langs->trans('Asset').' prêté' : 'Liste des '.$langs->trans('Asset')
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
			'unite'=>'get_measuring_units_string(@ID@,"@unite@")'
			,'label'=>'_get_product_link(@fk_product@, "@val@")'
		)
	));

	if(isset($_REQUEST['fk_product'])){
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="fiche.php?action=edit&fk_soc='.$fk_soc.'&fk_product='.$product->id.'&fk_asset_type='.$fk_asset_type.'">'.$langs->trans('CreateAsset').'</a>';
		echo '</div>';
	}
	
	$ATMdb->close();

	llxFooter('$Date: 2011/07/31 23:19:25 $ - $Revision: 1.152 $');

}

function _get_product_link($fk_product=null, $label) {
    global $db;

    if (!empty($fk_product)) {
	    $p=new Product($db);
	    $p->fetch($fk_product);
	    $p->ref.=' '.$label;
	    
	    return $p->getNomUrl(1);
    }
    else {
	    return 'Produit non défini.';
    }
}
?>