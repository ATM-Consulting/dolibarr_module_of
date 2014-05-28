<?php

function assetPrepareHead(&$asset,$type='type-asset') {
	global $user;
	
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
			return array(
					array(DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$asset->getId(), 'Fiche','fiche')
				);
		case 'assetlot':
			return array(
					array(DOL_URL_ROOT.'/custom/asset/fiche_lot.php?id='.$asset->getId(), 'Fiche','fiche')
				);
			break;
	}
}