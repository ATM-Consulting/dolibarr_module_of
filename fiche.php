<?php

require('config.php');

require('./class/asset.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	
$ATMdb=new Tdb;

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

	if(isset($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'add':
				$asset=new TAsset();
				$asset->set_values($_REQUEST);
	
				$asset->save($ATMdb);
				_fiche($asset,'edit');
				
				break;	
			case 'edit'	:
				$asset=new TAsset;
				$asset->load($ATMdb, $_REQUEST['id']);
				
				_fiche($asset,'edit');
				break;
			case 'save':
				$asset=new TAsset;
				$asset->load($ATMdb, $_REQUEST['id']);
				$asset->set_values($_REQUEST);
				
				//$ATMdb->db->debug=true;
				//print_r($_REQUEST);
				
				$asset->save($ATMdb);
				
				_fiche($asset,'view');
				
				break;
			case 'clone':
				$asset=new TAsset;
				$asset->load($ATMdb, $_REQUEST['id']);
				$asset->reinit();
				$asset->serial_number.='(copie)';
				//$ATMdb->db->debug=true;
				$asset->save($ATMdb);
				
				_fiche($asset,'view');
				
				break;
				
			case 'delete':
				$asset=new TAsset;
				$asset->load($ATMdb, $_REQUEST['id']);
				//$ATMdb->db->debug=true;
				$asset->delete($ATMdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste.php?delete_ok=1";					
				</script>
				<?
				
				break;
		}
		
	}
	elseif(isset($_REQUEST['id'])) {
		$asset=new TAsset;
		$asset->load($ATMdb, $_REQUEST['id']);
		
		_fiche($asset, 'view');
	}


	$ATMdb->close();
	
}

function _fiche(&$asset, $mode='edit') {

/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/
	
	llxHeader('','Equipement','','');
	
	$form=new TFormCore($_SERVER['PHP_SELF'],'formeq','POST');
	$form->Set_typeaff($mode);
	
	echo $form->hidden('id', $asset->rowid);
	echo $form->hidden('action', 'save');
	
	
	if($mode=='view') {
		?>
		<div class="fiche"> <!-- begin div class="fiche" -->
		
		<div class="tabs">
		<a class="tabTitle"><img border="0" title="" alt="" src="<?=DOL_MAIN_URL_ROOT_ALT ?>/atm-core/img/object_technic.png"> Equipement</a>
		<a href="<?=$_SERVER['PHP_SELF' ]?>?id=<?=$asset->rowid ?>" class="tab" id="active">Fiche</a>
		</div>
		
			<div class="tabBar"><?
		
	}
	/*
	 * affichage données équipement 
	 */	
		
		?><table width="100%" class="border">
			<tr><td width="20%">Numéro de série</td><td><?=$form->texte('', 'serial_number', $asset->serial_number, 100,255,'','','à saisir') ?></td></tr>
			<tr><td>Périodicité (en jours)</td><td><?=$form->texte('', 'periodicity', $asset->periodicity, 8,10,'','','à saisir') ?></td></tr>
			<tr><td>Produit</td><td><?=_fiche_visu_produit($asset,$mode); ?></td></tr>
			<tr><td>Société</td><td><?=_fiche_visu_societe($asset,$mode); ?></td></tr>
			<tr><td>Affaire</td><td><?=_fiche_visu_affaire($asset,$mode); ?></td></tr>
			<tr><td>date d'achat</td><td><?=$form->calendrier('', 'date_achat', $asset->get_date('date_achat'),10) ?></td></tr>
			<tr><td>date de livraison</td><td><?=$form->calendrier('', 'date_shipping', $asset->get_date('date_shipping') ,10) ?></td></tr>
			<tr><td>date de garantie</td><td><?=$form->calendrier('', 'date_garantie', $asset->get_date('date_garantie'),10) ?></td></tr>
			<tr><td>date de dernière intervention</td><td><?=$form->calendrier('', 'date_last_intervention', $asset->get_date('date_last_intervention'),10) ?></td></tr>

			<tr><td>Coût copie noir & blanc</td><td><?=$form->texte('', 'copy_black', $asset->copy_black, 12,10,'','','0.00') ?></td></tr>
			<tr><td>Coût copie couleur</td><td><?=$form->texte('', 'copy_color', $asset->copy_color, 12,10,'','','0.00') ?></td></tr>

			</table>
		<?
	
	if($mode==view) {
	
	?></div>

		</div>
		
		<div class="tabsAction">
		<input type="button" id="action-delete" value="Supprimer" name="cancel" class="button" onclick="document.location.href='<?=$_SERVER['PHP_SELF']?>?action=delete&id=<?=$asset->rowid ?>'">
		&nbsp; &nbsp; <input type="button" id="action-clone" value="Cloner" name="cancel" class="button" onclick="document.location.href='<?=$_SERVER['PHP_SELF']?>?action=clone&id=<?=$asset->rowid ?>'">
		&nbsp; &nbsp; <a href="<?=$_SERVER['PHP_SELF' ]?>?id=<?=$asset->rowid ?>&action=edit" class="butAction">Modifier</a>
		</div><?

	}
	else {
		
		?>
		<p align="center">
			<input type="submit" value="Enregistrer" name="save" class="button"> 
			&nbsp; &nbsp; <input type="button" value="Annuler" name="cancel" class="button" onclick="document.location.href='<?=$_SERVER['PHP_SELF']?>?id=<?=$asset->rowid ?>'">
		</p>
		<?
	}

	echo $form->end_form();
	// End of page
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}

function _fiche_visu_produit(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		$html=new Form($db);
		$html->select_produits($asset->fk_product,'fk_product','',$conf->product->limit_size);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_product > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
			
			$product = new Product($db);
			$product->fetch($asset->fk_product);
				
			return '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$asset->fk_product.'" style="font-weight:bold;"><img border="0" src="'.DOL_URL_ROOT.'/theme/atm/img/object_product.png"> '. $product->label.'</a>';
		} else {
			return 'Non défini';
		}
	}
}
function _fiche_visu_societe(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			$soc->fetch($asset->fk_soc);	
				
			return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$asset->fk_soc.'" style="font-weight:bold;"><img border="0" src="'.DOL_URL_ROOT.'/theme/atm/img/object_company.png"> '.$soc->nom.'</a>';
		} else {
			return 'Non défini';
		}
	}
}

function _fiche_visu_affaire(&$asset, $mode) {
global $db;
	
	if($mode=='edit') {
		ob_start();	
		
		$html=new Form($db);
		echo $html->select_company($asset->fk_soc,'fk_soc','',1);
		
		return ob_get_clean();
		
	}
	else {
		if($asset->fk_soc > 0) {
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			
			$soc = new Societe($db);
			$soc->fetch($asset->fk_soc);	
				
			return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$asset->fk_soc.'" style="font-weight:bold;"><img border="0" src="'.DOL_URL_ROOT.'/theme/atm/img/object_company.png"> '.$soc->nom.'</a>';
		} else {
			return 'Non défini';
		}
	}
}

?>
