<?php
	
	require '../config.php';
	//require('../lib/asset.lib.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	
	global $user,$langs;
	
	$langs->load('asset@asset');
	
	if (!($user->admin)) accessforbidden();
	
	$action=__get('action','');

	if($action=='save') {
		
		foreach($_REQUEST['TAsset'] as $name=>$param) {
			
			dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);
			
		}
		
		setEventMessage("Configuration enregistrée");
	}
	
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
	print '<td>'.$langs->trans("UsetAssetProductionAttributs").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_USE_PRODUCTION_ATTRIBUT');
	print '</td></tr>';	
	
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
	print "</table>";
	
	$form=new TFormCore;

	showParameters($form);

function showParameters(&$form) {
	global $db,$conf,$langs;
	dol_include_once('/product/class/html.formproduct.class.php');
	
	$formProduct = new FormProduct($db);
	
	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="load-<?php echo $typeDoc ?>" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="save" />
		<table width="100%" class="noborder" style="background-color: #fff;">
			<tr class="liste_titre">
				<td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
			</tr>
			
			<tr>
				<td><?php echo $langs->trans('DefaultWarehouseId') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID,'TAsset[ASSET_DEFAULT_WAREHOUSE_ID]'); ?></td>
			</tr>
		</table>
		<p align="right">	
			<input type="submit" name="bt_save" value="<?php echo $langs->trans('Save') ?>" /> 
		</p>
	
	</form>
	
	
	<br /><br />
	<?php
}
	
	//$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';