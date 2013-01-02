<?php
	require('../atm-core/inc-dolibarr.php');
	
	_liste($user->entity);

function _liste($id_entity) {
global $langs,$db,$user;
	

	llxHeader('','Liste des équipements installés','','');
	
	if(isset($_REQUEST['delete_ok'])) {
		?>
		<br><div class="error">Equipement supprim&eacute;</div><br>
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
		require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			
		$product = new Product($db);
		$result=$product->fetch($_REQUEST['fk_product']);	
			
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'tabEquipement1', $titre, 0, $picto);
		
	}
	
	else{
		print load_fiche_titre('Equipements');
	}


	$form=new ATMForm;

 
 	$table = 'llx_asset';
	$listname = 'list_equipement';
	$lst = new Tlistview($listname);
	
	$order = (isset($_REQUEST['relance'])) ? 'A' : 'D';
	$ordercol = (isset($_REQUEST['relance'])) ? 'Date garantie' : 'Date création';
    $ordertype = isset($_REQUEST["orderTyp"])?$_REQUEST["orderTyp"]:$order;
    $pagenumber = isset($_REQUEST["pageNumber"])?$_REQUEST["pageNumber"]:0;
 	$ordercolumn = isset($_REQUEST["orderColumn"])?$_REQUEST["orderColumn"]:$ordercol ;

	$lst->Set_nbLinesPerPage(30);
			
	$sql="SELECT e.rowid as 'ID',e.serial_number as 'Numéro de série', p.label as 'Produit',s.nom as 'Société',
			e.date_garantie as 'Date garantie', e.date_last_intervention as 'Date dernière intervention', e.date_cre as 'Date création'
	
	FROM ((".$table." e LEFT OUTER JOIN llx_product p ON (e.fk_product=p.rowid))
				LEFT OUTER JOIN llx_societe s ON (e.fk_soc=s.rowid))
	
	WHERE 1 ";
	$fk_soc=0;$fk_product=0;
	if(isset($_REQUEST['fk_soc'])) {$sql.=" AND e.fk_soc=".$_REQUEST['fk_soc']; $fk_soc=$_REQUEST['fk_soc'];}
	if(isset($_REQUEST['fk_product'])){$sql.=" AND e.fk_product=".$_REQUEST['fk_product']; $fk_product=$_REQUEST['fk_product'];}
	
	if($fk_soc==0 && $fk_product==0 && $id_entity!=0) {
		$sql.= ' AND e.entity='.$id_entity;		
	}	
	if(isset($_REQUEST['no_serial'])) {
		$sql.=" AND serial_number='' ";		
	}
		
	$lst->Set_query($sql);
	$lst->Load_query($ordercolumn,$ordertype);
	$lst->Set_pagenumber($pagenumber);
	$lst->Set_Key("ID",'id');
		
	$lst->Set_hiddenColumn('ID', true);
	$lst->Str_trans('Numéro de série', array(),utf8_decode('Numéro à saisir'));
			
	$lst->Set_columnType('Date création', 'DATE');
	$lst->Set_columnType('Date garantie', 'DATE');
	$lst->Set_columnType('Date dernière intervention', 'DATE');
		
	$lst->Set_OnClickAction('OpenForm','fiche.php?');
	 
	echo $lst->Render("Il n'y a pas d'équipement définis."); 	
		 
	echo "<p align=\"center\">";	
	echo $form->bt("Nouveau",'bt_new','onClick="document.location.href=\'fiche.php?action=add&fk_soc='.$fk_soc.'&fk_product='.$fk_product.'\'"');
	echo "</p>";

	llxFooter('$Date: 2011/07/31 23:19:25 $ - $Revision: 1.152 $');
	
}
?>