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
dol_include_once('abricot/includes/lib/admin.lib.php');


// Translations
$langs->load('admin');
$langs->load("of@of");

// Access control
if (! $user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'none');
$value = GETPOST('value', 'none');
$label = GETPOST('label', 'none');
$scandir = GETPOST('scan_dir', 'none');
$type='of';

// Parameters
$action = GETPOST('action', 'none');


/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code, 'none'), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
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

if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

elseif ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if (getDolGlobalString('OF_ADDON_PDF') == "$value") dolibarr_del_const($db, 'OF_ADDON_PDF', $conf->entity);
	}
}
// Set default model
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "OF_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->OF_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

if($action=='save') {

	$error=0;

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
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = ofAdminPrepareHead();
dol_fiche_head(
	$head,
	'models',
	$langs->trans("Module104161Name"),
	1,
	"of@of"
);


if(!function_exists('setup_print_title')){
	print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
	exit;
}


// MODELS ODT AVEC TBS
$Tform=new TFormCore;
showParameters($Tform);


// Setup page goes here
$form=new Form($db);
/*
 *  Document templates generators (std Dolibarr)
 *  Copy of invoice document code
 */
print '<br>';
print load_fiche_titre($langs->trans("OfPDFModules"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
$activatedModels = array();

foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/of".$valdir);

		if (is_dir($dir))
		{
			$handle=opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					$filelist[]=$file;
				}
				closedir($handle);
				arsort($filelist);

				foreach($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development'  && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) $modulequalified=0;
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) $modulequalified=0;

							if ($modulequalified)
							{
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								}
								else
								{
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("SetAsDefault"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print '<td class="center">';
								if (getDolGlobalString('OF_ADDON_PDF') == $name)
								{
									print img_picto($langs->trans("Default"), 'on');
								}
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';






function showParameters(&$form) {
	global $db,$conf,$langs;
	dol_include_once('/product/class/html.formproduct.class.php');

	$formProduct = new FormProduct($db);

	?><form action="<?php echo $_SERVER['PHP_SELF'] ?>" name="load-models" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="action" value="save" />
		<table width="100%" class="noborder">
			<tr class="liste_titre">
				<td colspan="2"><?php echo $langs->trans('TemplateOF') ?></td>
			</tr>
			<tr class="pair" >
				<td><?php echo $langs->trans('Template') ?></td><td>
					<input type="file" name="template" />
					<?php
						if (!empty(getDolGlobalString('TEMPLATE_OF'))) $template = $conf->global->TEMPLATE_OF;
						else $template = "templateOF.odt";

						$locationTemplate = DOL_DATA_ROOT.'/of/template/'.$template;

						if (!file_exists($locationTemplate)) $url = dol_buildpath('/of/exempleTemplate/'.$template, 1);
						else $url = dol_buildpath('document.php', 1).'?modulepart=of&file=/template/'.$template;

					 echo ' - <a href="'.$url.'">'.$langs->trans('Download').'</a> '.$template;
				 ?></td>
			</tr>
		</table>

		<p align="right">
			<input class="button" type="submit" name="bt_save" value="<?php echo $langs->trans('Save') ?>" />
		</p>

	</form>

	<?php
}

dol_fiche_end(1);

llxFooter();

$db->close();

function _getDateExtrafields($elementtype){
    global $db;
    dol_include_once('/core/class/extrafields.class.php');
    $extra = new ExtraFields($db);
    $extra->fetch_name_optionals_label($elementtype);
    if(!empty($extra->attributes[$elementtype]['label'])) return $extra->attributes[$elementtype]['label'];
    else return array();
}
