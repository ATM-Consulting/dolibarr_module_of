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
		
		llxHeader('',$langs->trans('ListControl'),'','');
		getStandartJS();

		if (isset($_SESSION['AssetMsg']))
		{
			print_r('<div class="info">'.$langs->trans($_SESSION['AssetMsg']).'</div>');
			unset($_SESSION['AssetMsg']);
		}
		
		$form=new TFormCore;
		$assetControl = new TAssetControl;
		$r = new TSSRenderControler($assetControl);
	
		$sql = 'SELECT rowid as id, libelle, type, question, "" as action FROM '.MAIN_DB_PREFIX.'asset_control';
		
		$THide = array('rowid');
	
		$form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');
	
		$ATMdb=new TPDOdb;
	
		$r->liste($ATMdb, $sql, array(
			'limit'=>array(
				'nbLine'=>'30'
			)
			,'subQuery'=>array()
			,'link'=>array(
				'libelle'=>'<a href="'.DOL_URL_ROOT.'/custom/asset/control.php?id=@id@">@val@</a>'
				,'question'=>'<a href="'.DOL_URL_ROOT.'/custom/asset/control.php?id=@id@">@val@</a>'
				,'action'=>'<a title="Modifier" href="control.php?id=@id@&action=edit">'.img_picto('','edit.png', '', 0).'</a>&nbsp;&nbsp;&nbsp;<a title="Supprimer" onclick="if (!window.confirm(\'Confirmez-vous la suppression ?\')) return false;" href="control.php?id=@id@&action=delete">'.img_picto('','delete.png', '', 0)."</a>"
			)
			,'search'=>array(
				'libelle'=>array('recherche'=>true, 'table'=>'')
			)
			,'translate'=>array()
			,'hide'=>$THide
			,'liste'=>array(
				'titre'=>$langs->trans('ListControl')
				,'image'=>img_picto('','title.png', '', 0)
				,'picto_precedent'=>img_picto('','back.png', '', 0)
				,'picto_suivant'=>img_picto('','next.png', '', 0)
				,'noheader'=> 0
				,'messageNothing'=>$langs->trans('AssetEmptyControl')
				,'picto_search'=>img_picto('','search.png', '', 0)
			)
			,'title'=>array(
				'libelle'=>'Libelle'
				,'type'=>'Type'
				,'nb_value'=>'Nombre de valeurs associés'
				,'question'=>'Question'
				,'action'=>'Action'
			)
			,'eval'=>array(
				'type'=>'TAssetControl::$TType["@val@"]'
			)
		));
		
		$form->end();
		
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="control.php?action=new">'.$langs->trans('AssetCreateControl').'</a>';
		echo '</div>';
	
	
		$ATMdb->close();		
		llxFooter('');
	}
