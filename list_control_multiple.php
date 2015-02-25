<?php
	require('config.php');
	require('./class/asset.class.php');
	require('./class/ordre_fabrication_asset.class.php');
	
	if(!$user->rights->asset->of->lire) accessforbidden();
	
	dol_include_once("/core/class/html.formother.class.php");
	
	$langs->load('asset@asset');
	_liste();

	function _liste() {
		global $langs,$db,$user,$conf;
		
		$langs->load('asset@asset');
		
		llxHeader('',$langs->trans('ListControlMultiple'),'','');
		getStandartJS();

		if (isset($_SESSION['AssetMsg']))
		{
			print_r('<div class="info">'.$langs->trans($_SESSION['AssetMsg']).'</div>');
			unset($_SESSION['AssetMsg']);
		}
		
		$form=new TFormCore;
		$assetControl = new TAssetControl;
		$r = new TSSRenderControler($assetControl);
	
		$sql = 'SELECT cm.rowid as id, cm.fk_control, c.libelle, cm.value, "" as action FROM '.MAIN_DB_PREFIX.'asset_control_multiple cm ';
		$sql.= 'INNER JOIN '.MAIN_DB_PREFIX.'asset_control c ON (c.rowid = cm.fk_control)';
		
		$THide = array('rowid', 'fk_control');
	
		$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');
	
		$ATMdb=new TPDOdb;
	
		$r->liste($ATMdb, $sql, array(
			'limit'=>array(
				'nbLine'=>'30'
			)
			,'subQuery'=>array()
			,'link'=>array(
				'libelle'=>'<a href="'.DOL_URL_ROOT.'/custom/asset/control.php?id=@fk_control@">@val@</a>'
				,'action'=>'<a title="Modifier" href="control.php?idm=@id@&action=editValue">'.img_picto('','edit.png', '', 0).'</a>&nbsp;&nbsp;&nbsp;<a title="Supprimer" onclick="if (!window.confirm(\'Confirmez-vous la suppression ?\')) return false;" href="control.php?idm=@id@&action=deleteValue">'.img_picto('','delete.png', '', 0)."</a>"
			)
			,'search'=>array(
				'libelle'=>array('recherche'=>true, 'table'=>'c')
				,'value'=>array('recherche'=>true, 'table'=>'')
			)
			,'translate'=>array()
			,'hide'=>$THide
			,'liste'=>array(
				'titre'=>$langs->trans('ListControlMultiple')
				,'image'=>img_picto('','title.png', '', 0)
				,'picto_precedent'=>img_picto('','back.png', '', 0)
				,'picto_suivant'=>img_picto('','next.png', '', 0)
				,'noheader'=> 0
				,'messageNothing'=>$langs->trans('AssetEmptyControlMultiple')
				,'picto_search'=>img_picto('','search.png', '', 0)
			)
			,'title'=>array(
				'libelle'=>'Libellé du contrôle'
				,'value'=>'Valeur'
				,'action'=>'Action'
			)
		));
		
		$form->end();
		
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="control.php?action=addValue">'.$langs->trans('AssetCreateControlMultiple').'</a>';
		echo '</div>';
	
	
		$ATMdb->close();		
		llxFooter('');
	}
