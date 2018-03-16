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
$langs->load('admin');
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

		$error=0;

		if(isset($_REQUEST['TOF']))
		{
			foreach($_REQUEST['TOF'] as $name=>$param) {

				dolibarr_set_const($db, $name, $param, 'chaine', 0, '', $conf->entity);

			}
		}

		if(isset($_FILES['template']) && !empty($_FILES['template']['tmp_name']))
		{
			$src=$_FILES['template']['tmp_name'];
			$dirodt=DOL_DATA_ROOT.'/of/template/';
			$dest=$dirodt.'/'.$_FILES['template']['name'];

			if (file_exists($src))
			{
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_mkdir($dirodt);
				$result=dol_copy($src,$dest,0,1);
				if ($result < 0)
				{
					$error++;
					$langs->load("errors");
					setEventMessage($langs->trans('ErrorFailToCopyFile',$src,$dest));
				}
				else
				{
					dolibarr_set_const($db, 'TEMPLATE_OF', $_FILES['template']['name'], 'chaine', 0, '', $conf->entity);
				}
			}
		}

		if (!$error) setEventMessage($langs->trans("SetupSaved"));
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
$formCore=new TFormcore;
// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_MASK").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_OF_MASK">';

print $formCore->texte('', 'OF_MASK', (empty($conf->global->OF_MASK) ? '' : $conf->global->OF_MASK), 20,255,' placeholder="OF{00000}" ');


dol_include_once('/of/class/ordre_fabrication_asset.class.php');
$assetOf=new TAssetOF();
echo ' - prochain numéro : '.$assetOf->getNumero($PDOdb,false).' ';

print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CreateAssetChildrenOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_CHILDREN_OF', array('alert' => array('method'=>'fnHideOPCAAdrr' ,'del' => array('content'=>$langs->trans('AssetOFConfirmChangeState'), 'title'=>$langs->trans('AssetOFConfirmChangeStateTitle'))), 'del' => array('CREATE_CHILDREN_OF_COMPOSANT', 'CREATE_OF_ON_ORDER_VALIDATE', 'DELETE_OF_ON_ORDER_CANCEL')));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CreateAssetChildrenOFOnVirtualStock").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_CHILDREN_OF_ON_VIRTUAL_STOCK');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MinimalViewForChilOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_MINIMAL_VIEW_CHILD_OF');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CreateAssetChildrenOFWithComposant").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_CHILDREN_OF_COMPOSANT', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';

if(!empty($conf->asset->enabled)) {

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("UseBatchNumberInOf").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('USE_LOT_IN_OF');
	print '</td></tr>';

}

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssetAddNeededQtyZero").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_ADD_NEEDED_QTY_ZERO');
print '</td></tr>';

$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AssetNegativeDestock").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_NEGATIVE_DESTOCK');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AssetChildOfStatusFollowParentStatus").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_CHILD_OF_STATUS_FOLLOW_PARENT_STATUS');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OF_CHECK_IF_WAREHOUSE_ON_OF_LINE").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_CHECK_IF_WAREHOUSE_ON_OF_LINE');
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OF_PRINT_IN_PDF").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_PRINT_IN_PDF');
    print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("AssetConcatPDF").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="300">';
	print ajax_constantonoff('ASSET_CONCAT_PDF');
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->transnoentitiesnoconv("AssetUseDestockagePartiel").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_USE_DESTOCKAGE_PARTIEL');
    print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OfShowQtytheorique").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_SHOW_QTY_THEORIQUE_MOINS_OF');
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OF_SHOW_ORDER_LINE_PRICE").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_SHOW_ORDER_LINE_PRICE');
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OF_SHOW_LINE_ORDER_EXTRAFIELD").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_SHOW_LINE_ORDER_EXTRAFIELD');
    print '</td></tr>';

    if(!empty($conf->global->OF_SHOW_LINE_ORDER_EXTRAFIELD)) {


        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td>'.$langs->trans("OF_SHOW_LINE_ORDER_EXTRAFIELD_JUST_THEM").'</td>';
        print '<td align="center" width="20">&nbsp;</td>';
        print '<td align="right" width="300" style="white-space:nowrap;">';
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="set_OF_SHOW_LINE_ORDER_EXTRAFIELD_JUST_THEM">';
        print $formCore->texte('', 'OF_SHOW_LINE_ORDER_EXTRAFIELD_JUST_THEM', (empty($conf->global->OF_SHOW_LINE_ORDER_EXTRAFIELD_JUST_THEM) ? '' : $conf->global->OF_SHOW_LINE_ORDER_EXTRAFIELD_JUST_THEM), 80,255,' placeholder="" ');
        print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
        print '</form>';
        print '</td></tr>';

    }

    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OF_SHOW_LINE_ORDER_EXTRAFIELD_COPY_TO_TASK").'</td>';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print ajax_constantonoff('OF_SHOW_LINE_ORDER_EXTRAFIELD_COPY_TO_TASK');
    print '</td></tr>';

   $var=!$var;
	print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("OfNbTicketrPerPage").'</td>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<td align="center" width="20">&nbsp;</td>';
    print '<td align="center" width="300">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="set_OF_NB_TICKET_PER_PAGE">';
    print $formCore->number("", "OF_NB_TICKET_PER_PAGE",$conf->global->OF_NB_TICKET_PER_PAGE,10,1,-1);
    print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    print '</form>';
    print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("set_ABRICOT_WKHTMLTOPDF_CMD").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300" style="white-space:nowrap;">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_ABRICOT_WKHTMLTOPDF_CMD">';
	print $formCore->texte('', 'ABRICOT_WKHTMLTOPDF_CMD', (empty($conf->global->ABRICOT_WKHTMLTOPDF_CMD) ? '' : $conf->global->ABRICOT_WKHTMLTOPDF_CMD), 80,255,' placeholder="wkhtmltopdf" ');
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("CHOOSE_CUSTOM_LABEL").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_DEFAULT_ETIQUETTES">';
	$liste = array(1 => 'etiquette.html', 2 => 'etiquette_custom.html');
	print $form->selectarray('DEFAULT_ETIQUETTES', $liste, $conf->global->DEFAULT_ETIQUETTES);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';


	if($conf->global->DEFAULT_ETIQUETTES == 2){

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_MARGIN_TOP").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_MARGIN_TOP">';
			print $formCore->texte('', 'DEFINE_MARGIN_TOP', $conf->global->DEFINE_MARGIN_TOP, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_MARGIN_TOP_CELL").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_MARGIN_TOP_CELL">';
			print $formCore->texte('', 'DEFINE_MARGIN_TOP_CELL', $conf->global->DEFINE_MARGIN_TOP_CELL, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_MARGIN_LEFT").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_MARGIN_LEFT">';
			print $formCore->texte('', 'DEFINE_MARGIN_LEFT', $conf->global->DEFINE_MARGIN_LEFT, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_MARGIN_RIGHT").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_MARGIN_RIGHT">';
			print $formCore->texte('', 'DEFINE_MARGIN_RIGHT', $conf->global->DEFINE_MARGIN_RIGHT, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_WIDTH_DIV").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_WIDTH_DIV">';
			print $formCore->texte('', 'DEFINE_WIDTH_DIV', $conf->global->DEFINE_WIDTH_DIV, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

			print '<tr '.$bc[$var].'>';
			print '<td>'.$langs->trans("DEFINE_HEIGHT_DIV").'</td>';
			print '<td align="center" width="20">&nbsp;</td>';
			print '<td align="right" width="300">';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="set_DEFINE_HEIGHT_DIV">';
			print $formCore->texte('', 'DEFINE_HEIGHT_DIV', $conf->global->DEFINE_HEIGHT_DIV, 10, 10);
			print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			print '</form>';
			print '</td></tr>';

	}



	print '</table>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParametersWorkstation").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseProjectTask").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_USE_PROJECT_TASK');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseProjectTaskHierarchique").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_TASK_HIERARCHIQUE_BY_RANK');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_CONCAT_WS_ON_ADD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_CONCAT_WS_ON_ADD');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssetDefinedUserByWorkstation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_USER_BY_WORKSTATION');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssetDefinedTaskByWorkstation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_OPERATION_BY_WORKSTATION');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssetUseWorkstationByNeededInOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_DEFINED_WORKSTATION_BY_NEEDED');
print '</td></tr>';


$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_USE_APPRO_DELAY_FOR_TASK_DELAY").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_USE_APPRO_DELAY_FOR_TASK_DELAY');
print '</td></tr>';


$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("set_OF_COEF_WS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300" style="white-space:nowrap;">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_OF_COEF_WS">';
print $formCore->texte('', 'OF_COEF_WS', (empty($conf->global->OF_COEF_WS) ? '' : $conf->global->OF_COEF_WS), 5,255);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';



$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_SHOW_WS_IN_LIST").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_SHOW_WS_IN_LIST');
print '</td></tr>';


print '</table>';



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ParametersWorkflow").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CreteAssetOFOnOrderValidation").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CREATE_OF_ON_ORDER_VALIDATE', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DeleteAssetOFOnOrderCancel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('DELETE_OF_ON_ORDER_CANCEL', array('set' => array('CREATE_CHILDREN_OF' => 1)));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AssetAutoCreateProjectOnOF").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('ASSET_AUTO_CREATE_PROJECT_ON_OF');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_ALLOW_FINISH_OF_WITH_UNRECEIVE_ORDER").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_ALLOW_FINISH_OF_WITH_UNRECEIVE_ORDER');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("OF_FOLLOW_SUPPLIER_ORDER_STATUS").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('OF_FOLLOW_SUPPLIER_ORDER_STATUS');
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
						if (!empty($conf->global->TEMPLATE_OF)) $template = $conf->global->TEMPLATE_OF;
						else $template = "templateOF.odt";

						$locationTemplate = DOL_DATA_ROOT.'/of/template/'.$template;

						if (!file_exists($locationTemplate)) $url = dol_buildpath('/of/exempleTemplate/'.$template, 1);
						else $url = dol_buildpath('document.php', 1).'?modulepart=of&file=/template/'.$template;

					 echo ' - <a href="'.$url.'">'.$langs->trans('Download').'</a> '.$template;
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

	   <a href="http://www.atm-consulting.fr/" target="_blank"><img src="../img/ATM_logo_petit.jpg" /></a>
	</p>

	<br /><br />
	<?php
}

llxFooter();

$db->close();
