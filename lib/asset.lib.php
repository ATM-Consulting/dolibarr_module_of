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

function _getArrayNomenclature(&$PDOdb, &$TAssetOFLine)
{
	global $conf;
	
	$TRes = array();
	
	if (empty($conf->global->ASSET_USE_MOD_NOMENCLATURE)) return $TRes;
	
	include_once DOL_DOCUMENT_ROOT.'/custom/nomenclature/class/nomenclature.class.php';
	
	$TNomen = TNomenclature::get($PDOdb, $TAssetOFLine->fk_product);
	foreach ($TNomen as $TNomenclature) 
	{
		$TRes[$TNomenclature->getId()] = !empty($TNomenclature->title) ? $TNomenclature->title : '(sans titre)';
	}
	
	return $TRes;
}

function  _getTitleNomenclature(&$PDOdb, $fk_nomenclature)
{
	global $conf;
	
	if (empty($conf->global->ASSET_USE_MOD_NOMENCLATURE)) return '';
	
	include_once DOL_DOCUMENT_ROOT.'/custom/nomenclature/class/nomenclature.class.php';
	
	$TNomen = new TNomenclature;
	$TNomen->load($PDOdb, $fk_nomenclature);
	
	return ($TNomen ? $TNomen->title : '');
}
