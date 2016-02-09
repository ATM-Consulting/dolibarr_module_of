<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/of.php
 * 	\ingroup	of
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment

require '../config.php';

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/of.lib.php';

// Translations
$langs->load("of@of");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	if($action=='save') {
		
		if(isset($_REQUEST['TOF']))
		{
			foreach($_REQUEST['TOF'] as $name=>$param) {
				
				dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);
				
			}
		}
		if(isset($_FILES['template']) && !empty($_FILES['template']['tmp_name'])) {
			
			copy($_FILES['template']['tmp_name'],'../exempleTemplate/templateOF.odt');
			
		}
		
		setEventMessage("Configuration enregistrée");
		
	}
/*
 * View
 */
$page_name = "ofSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = ofAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104161Name"),
    0,
    "of@of"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

print '<tr class="pair">';
print '<td>'.$langs->trans("CreateAssetChildrenOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_CHILDREN_OF', array('alert' => array('method'=>'fnHideOPCAAdrr' ,'del' => array('content'=>$langs->trans('AssetOFConfirmChangeState'), 'title'=>$langs->trans('AssetOFConfirmChangeStateTitle'))), 'del' => array('CREATE_CHILDREN_OF_COMPOSANT', 'CREATE_OF_ON_ORDER_VALIDATE', 'DELETE_OF_ON_ORDER_CANCEL')));
print '</td></tr>';	

print '<tr class="impair">';
print '<td>'.$langs->trans("CreateAssetChildrenOFWithComposant").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_CHILDREN_OF_COMPOSANT', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';	

if(!empty($conf->asset->enabled)) {
	
	print '<tr class="pair">';
	print '<td>'.$langs->trans("UseBatchNumberInOf").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('USE_LOT_IN_OF');
	print '</td></tr>';	
	
}
print '<tr class="pair">';
print '<td>'.$langs->trans("AssetChildOfStatusFollowParentStatus").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_CHILD_OF_STATUS_FOLLOW_PARENT_STATUS');
print '</td></tr>';

print '<tr class="impair">';
print '<td>'.$langs->trans("AssetConcatPDF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_CONCAT_PDF');
print '</td></tr>';

print '</table>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParametersWorkstation").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

print '<tr class="pair">';
print '<td>'.$langs->trans("UseProjectTask").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_USE_PROJECT_TASK');
print '</td></tr>'; 

print '<tr class="impair">';
print '<td>'.$langs->trans("UseProjectTaskHierarchique").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_TASK_HIERARCHIQUE_BY_RANK');
print '</td></tr>'; 

print '<tr class="pair">';
print '<td>'.$langs->trans("AssetDefinedUserByWorkstation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_USER_BY_WORKSTATION');
print '</td></tr>';

print '<tr class="impair">';
print '<td>'.$langs->trans("AssetDefinedTaskByWorkstation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_OPERATION_BY_WORKSTATION');
print '</td></tr>';

print '<tr class="pair">';
print '<td>'.$langs->trans("AssetUseWorkstationByNeededInOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_WORKSTATION_BY_NEEDED');
print '</td></tr>';	

print '</table>';



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParametersWorkflow").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

print '<tr class="impair">';
print '<td>'.$langs->trans("CreteAssetOFOnOrderValidation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_OF_ON_ORDER_VALIDATE', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';	

print '<tr class="pair">';
print '<td>'.$langs->trans("DeleteAssetOFOnOrderCancel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('DELETE_OF_ON_ORDER_CANCEL', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';	

print '<tr class="impair">';
print '<td>'.$langs->trans("AssetUseControl").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_USE_CONTROL');
print '</td></tr>';	

print '<tr class="pair">';
print '<td>'.$langs->trans("AssetAutoCreateProjectOnOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_AUTO_CREATE_PROJECT_ON_OF');
print '</td></tr>';	

print '<tr class="impair">';
print '<td>'.$langs->trans("AssetAuthorizeAddWorkstationTime0OnOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_AUTHORIZE_ADD_WORKSTATION_TIME_0_ON_OF');
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
		<table width="100%" class="noborder">
			<tr class="liste_titre">
				<td colspan="2"><?php echo $langs->trans('ParametersWarehouse') ?></td>
			</tr>
			
			<tr class="pair">
				<td><?php echo $langs->trans('UseManualWarehouse') ?></td><td><?php echo ajax_constantonoff('ASSET_MANUAL_WAREHOUSE'); ?></td>
			</tr> 
			
			<tr id="USE_DEFAULT_WAREHOUSE" class="impair">
				<td><?php echo $langs->trans('UseDefinedWarehouse') ?></td><td><?php echo ajax_constantonoff('ASSET_USE_DEFAULT_WAREHOUSE', array('showhide' => array('#WAREHOUSE_TO_MAKE', '#WAREHOUSE_NEEDED'), 'hide' => array('#WAREHOUSE_TO_MAKE', '#WAREHOUSE_NEEDED'))); ?></td>
			</tr> 
			
			<tr class="pair" id="WAREHOUSE_TO_MAKE" class="pair" <?php if (empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) echo "style='display:none;'" ?>>
				<td><?php echo $langs->trans('DefaultWarehouseIdToMake') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE,'TOF[ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE]'); ?></td>
			</tr>
			
			<tr class="impair" id="WAREHOUSE_NEEDED" <?php if (empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) echo "style='display:none;'" ?>>
				<td><?php echo $langs->trans('DefaultWarehouseIdNeeded') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED,'TOF[ASSET_DEFAULT_WAREHOUSE_ID_NEEDED]'); ?></td>
			</tr> 
			<tr class="liste_titre">
				<td colspan="2"><?php echo $langs->trans('TemplateOF') ?></td>
			</tr>
			<tr class="pair" >
				<td><?php echo $langs->trans('Template') ?></td><td>
					<input type="file" name="template" />
					<?php 
				
					 echo ' <a href="'.dol_buildpath('/of/exempleTemplate/templateOF.odt',1).'">'.$langs->trans('Download').'</a>';
				 ?></td>
			</tr> 
			
			
		</table>
		
		<script type="text/javascript">
			$(function() {
				$('#set_ASSET_MANUAL_WAREHOUSE').click(function() {
					if ($('#del_ASSET_USE_DEFAULT_WAREHOUSE').css('display') != 'none') {
						$('#del_ASSET_USE_DEFAULT_WAREHOUSE').click();
					}
				});
				
				$('#set_ASSET_USE_DEFAULT_WAREHOUSE').click(function() {
					if ($('#del_ASSET_MANUAL_WAREHOUSE').css('display') != 'none') {
						$('#del_ASSET_MANUAL_WAREHOUSE').click();
					}
				});
			});
		</script>
		
		<p align="right">	
			<input class="button" type="submit" name="bt_save" value="<?php echo $langs->trans('Save') ?>" /> 
		</p>
	
	</form>
	<p align="center" style="background: #fff;">
	   
	   <a href="http://www.atm-consulting.fr/" target="_blank"><img src="../img/ATM_logo.jpg" /></a>
	</p>
	
	<br /><br />
	<?php
}

llxFooter();

$db->close();