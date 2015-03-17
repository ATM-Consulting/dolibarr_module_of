<?php

	function assetPrepareHead(&$asset,$type='type-asset') {
		global $user, $conf;

		switch ($type) {
			case 'type-asset':
				return array(
					array(DOL_URL_ROOT.'/custom/asset/typeAsset.php?id='.$asset->getId(), 'Fiche','fiche')
					,array(DOL_URL_ROOT.'/custom/asset/typeAssetField.php?id='.$asset->getId(), 'Champs','field')
				);
				break;
			case 'asset':
				return array(
						array(DOL_URL_ROOT.'/custom/asset/fiche.php?id='.$asset->getId(), 'Fiche','fiche')
					);
				break;
			case 'assetOF':
				$res = array(array(DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$asset->getId(), 'Fiche','fiche'));
				if (!empty($conf->global->ASSET_USE_CONTROL)) $res[] = array(DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$asset->getId().'&action=control', 'Contrôle','controle');
				
				return $res;
				break;
			case 'assetlot':
				return array(
						array(DOL_URL_ROOT.'/custom/asset/fiche_lot.php?id='.$asset->getId(), 'Fiche','fiche')
					);
				break;
		}
		
	}
	
	
	function visu_checkbox_user(&$PDOdb, &$form, $group, $TUsers, $name, $status)
	{
		$include = array();
		
		$sql = 'SELECT u.lastname, u.firstname, uu.fk_user FROM '.MAIN_DB_PREFIX.'usergroup_user uu INNER JOIN '.MAIN_DB_PREFIX.'user u ON (uu.fk_user = u.rowid) WHERE uu.fk_usergroup = '.(int) $group;
		$PDOdb->Execute($sql);
		
		//Cette input doit être présent que si je suis en brouillon, si l'OF est lancé la présence de cette input va réinitialiser à vide les associations précédentes
		if ($status == 'DRAFT' && $form->type_aff == 'edit') $res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($PDOdb->Get_line()) 
		{
			if ($status == 'DRAFT' || (in_array($PDOdb->Get_field('fk_user'), $TUsers))) $res .= '<p style="margin:4px 0">'.$form->checkbox1($PDOdb->Get_field('lastname').' '.$PDOdb->Get_field('firstname'), $name, $PDOdb->Get_field('fk_user'), (in_array($PDOdb->Get_field('fk_user'), $TUsers) ? true : false), ($status == 'DRAFT' ? 'style="vertical-align:text-bottom;"' : 'disabled="disabled" style="vertical-align:text-bottom;"'), '', '', 'case_before').'</p>';
		}
		
		return $res;
	}

	
	function visu_checkbox_task(&$PDOdb, &$form, $fk_asset_workstation, $TTasks, $name, $status)
	{
		$include = array();
		
		$sql = 'SELECT rowid, libelle FROM '.MAIN_DB_PREFIX.'asset_workstation_task WHERE fk_workstation = '.(int) $fk_asset_workstation;
		$PDOdb->Execute($sql);

		//Cette input doit être présent que si je suis en brouillon, si l'OF est lancé la présence de cette input va réinitialiser à vide les associations précédentes
		if ($status == 'DRAFT' && $form->type_aff == 'edit') $res = '<input checked="checked" style="display:none;" type="checkbox" name="'.$name.'" value="0" />';
		while ($PDOdb->Get_line())
		{			 
			if ($status == 'DRAFT' || (in_array($PDOdb->Get_field('rowid'), $TTasks))) $res .= '<p style="margin:4px 0">'.$form->checkbox1($PDOdb->Get_field('libelle'), $name, $PDOdb->Get_field('rowid'), (in_array($PDOdb->Get_field('rowid'), $TTasks)), ($status == 'DRAFT' ? 'style="vertical-align:text-bottom;"' : 'disabled="disabled" style="vertical-align:text-bottom;"'), '', '', 'case_before').'</p>';
		}
		
		return $res;
	}
	