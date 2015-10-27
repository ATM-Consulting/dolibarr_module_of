<?php
	
	require '../config.php';
	//require('../lib/asset.lib.php');
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	
	global $user,$langs;
	
	$langs->load('asset@asset');
	$langs->load('admin');
	
	if (!($user->admin)) accessforbidden();
        
    
	$action=__get('action','');
    
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

	if($action=='save') {
		
		if(isset($_REQUEST['TAsset']))
		{
			foreach($_REQUEST['TAsset'] as $name=>$param) {
				
				dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);
				
			}
		}
		if(isset($_FILES['template']) && !empty($_FILES['template']['tmp_name'])) {
			
			copy($_FILES['template']['tmp_name'],'../exempleTemplate/templateOF.odt');
			
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
	
	print '<tr class="impair">';
	print '<td>'.$langs->trans("UsetAssetProductionAttributs").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_USE_PRODUCTION_ATTRIBUT');
	print '</td></tr>';	
	
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
	
	print '<tr class="pair">';
	print '<td>'.$langs->trans("UseBatchNumberInOf").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('USE_LOT_IN_OF');
	print '</td></tr>';	
	
	print '<tr class="impair">';
	print '<td>'.$langs->trans("AllBatchNumberAreMandatory").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('OF_LOT_MANDATORY');
	print '</td></tr>';	
	
	print '<tr class="pair">';
	print '<td>'.$langs->trans("AssetAddNeededQtyZero").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_ADD_NEEDED_QTY_ZERO');
	print '</td></tr>';	
	
	print '<tr class="impair">';
	print '<td>'.$langs->trans("AssetNegativeDestock").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_NEGATIVE_DESTOCK');
	print '</td></tr>';
	
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
    
// Setup page goes here
	$form=new TFormCore;

    print '<tr class="pair">';
    print '<td>'.$langs->trans("AssetDefaultDLUO").'</td>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="set_ASSET_DEFAULT_DLUO">';
    print $form->number("", "ASSET_DEFAULT_DLUO",$conf->global->ASSET_DEFAULT_DLUO,10);
    print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    print '</form>';
    print '</td></tr>';
	
	$form=new Form($db);

    print '<tr class="impair">';
    print '<td>'.$langs->trans("AssetDefaultLocatlisation").'</td>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="set_ASSET_DEFAULT_LOCATION">';
    print $form->select_company($conf->global->ASSET_DEFAULT_LOCATION, 'ASSET_DEFAULT_LOCATION', 's.client = 1 OR s.client = 3', 1);
    print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    print '</form>';
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
				<td><?php echo $langs->trans('DefaultWarehouseIdToMake') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE,'TAsset[ASSET_DEFAULT_WAREHOUSE_ID_TO_MAKE]'); ?></td>
			</tr>
			
			<tr class="impair" id="WAREHOUSE_NEEDED" <?php if (empty($conf->global->ASSET_USE_DEFAULT_WAREHOUSE)) echo "style='display:none;'" ?>>
				<td><?php echo $langs->trans('DefaultWarehouseIdNeeded') ?></td><td><?php echo $formProduct->selectWarehouses($conf->global->ASSET_DEFAULT_WAREHOUSE_ID_NEEDED,'TAsset[ASSET_DEFAULT_WAREHOUSE_ID_NEEDED]'); ?></td>
			</tr> 
			<tr class="liste_titre">
				<td colspan="2"><?php echo $langs->trans('TemplateOF') ?></td>
			</tr>
			<tr class="pair" >
				<td><?php echo $langs->trans('Template') ?></td><td>
					<input type="file" name="template" />
					<?php 
				
					 echo ' <a href="'.dol_buildpath('/asset/exempleTemplate/templateOF.odt',1).'">'.$langs->trans('Download').'</a>';
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
	   Développé par <br />
	   <a href="http://www.atm-consulting.fr/" target="_blank"><img src="../img/ATM_logo_petit.jpg" /></a>
	</p>
	
	<br /><br />
	<?php
}