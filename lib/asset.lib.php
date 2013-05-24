<?php

function assetPrepareHead(&$asset) {
	return array(
		array(DOL_URL_ROOT_ALT.'/asset/fiche.php?id='.$asset->getId(), 'Fiche','fiche')
		,array(DOL_URL_ROOT_ALT.'/asset/links.php?fk_asset='.$asset->getId(), 'Liens','links')
	);
	
}
	