<?php
	
	require '../config.php';
	//require('../lib/asset.lib.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	
	global $user;
	
	if (!($user->admin)) accessforbidden();
	
	llxHeader('','Gestion des équipements, à propos','');
	
	//$head = assetPrepareHead();
	dol_fiche_head($head, 'procedure', $langs->trans("Asset"), 0, 'recouvrementico@recouvrement');
	print_fiche_titre($langs->trans("AssetSetup"),$linkback);
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	
	print '<tr>';
	print '<td>'.$langs->trans("CreteAssetOFOnOrderValidation").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	
	print '<td align="center" width="300">';
	print ajax_constantonoff('CREATE_OF_ON_ORDER_VALIDATE');
	print '</td></tr>';	
	
	print '<tr>';
	print '<td>'.$langs->trans("DeleteAssetOFOnOrderCancel").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	
	print '<td align="center" width="300">';
	print ajax_constantonoff('DELETE_OF_ON_ORDER_CANCEL');
	print '</td></tr>';	
	print "</table";
	
	//$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';