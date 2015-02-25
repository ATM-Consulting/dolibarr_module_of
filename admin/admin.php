<?php
	
	require '../config.php';
	//require('../lib/asset.lib.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	
	global $user,$langs;
	
	$langs->load('asset@asset');
	$langs->load('admin');
	
	if (!($user->admin)) accessforbidden();
	
	$action=__get('action','');

	if($action=='save') {
		
		foreach($_REQUEST['TAsset'] as $name=>$param) {
			
			dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);
			
		}
		
		setEventMessage("Configuration enregistrée");
	}

	llxHeader('','Gestion des équipements, à propos', '');
	
	//$head = assetPrepareHead();
	$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
	dol_fiche_head($head, 1, $langs->trans("Asset"), 0, 'pictoof@asset');
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
	
	print '<tr>';
	print '<td>'.$langs->trans("CreateAssetChildrenOF").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('CREATE_CHILDREN_OF');
	print '</td></tr>';	
	
	print '<tr>';
	print '<td>'.$langs->trans("CreateAssetChildrenOFWithComposant").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('CREATE_CHILDREN_OF_COMPOSANT');
	print '</td></tr>';	
	
	print '<tr>';
	print '<td>'.$langs->trans("UseBatchNumberInOf").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('USE_LOT_IN_OF');
	print '</td></tr>';	
	
	print '<tr>';
	print '<td>'.$langs->trans("AssetDefinedUserByWorkstation").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_DEFINED_USER_BY_WORKSTATION');
	print '</td></tr>';
	
	print '<tr>';
	print '<td>'.$langs->trans("AssetUseWorkstationByNeededInOF").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_DEFINED_WORKSTATION_BY_NEEDED');
	print '</td></tr>';	
	
	print '<tr>';
	print '<td>'.$langs->trans("AssetUseControl").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_USE_CONTROL');
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
				<td colspan="2"><?php echo $langs->trans('ParametersWarehouse') ?></td>
			</tr>
			<?php /*
			<tr>
				<td><?php echo $langs->trans('UseManualWarehouse') ?></td><td><?php echo ajax_constantonoff('ASSET_MANUAL_WAREHOUSE'); ?></td>
			</tr> 
			*/ ?>
			<tr>
				<td><?php echo $langs->trans('DefaultWarehouseIdToMake') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE,'TAsset[ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE]'); ?></td>
			</tr>
			
			<tr>
				<td><?php echo $langs->trans('DefaultWarehouseIdNeeded') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED,'TAsset[ASSET_DEFAULT_WAREHOUSE_ID_NEEDED]'); ?></td>
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