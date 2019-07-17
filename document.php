<?php

/**
 *       \file       htdocs/product/document.php
 *       \ingroup    product
 *       \brief      Page des documents joints sur les produits
 */

require('config.php');

dol_include_once('/of/class/ordre_fabrication_asset.class.php');
dol_include_once('/of/lib/of.lib.php');
dol_include_once('/projet/class/project.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


// Load translation files required by the page
$langs->loadLangs(array('other', 'products'));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
$confirm= GETPOST('confirm','alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('ofdocuments'));

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="position_name";


$object = new TAssetOF;
$PDOdb = new TPDOdb;
if ($id > 0 || ! empty($ref))
{
    $result = $object->load($PDOdb, $id);

    $upload_dir = $conf->of->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'tassetof').dol_sanitizeFileName($object->ref);
}
$modulepart='tassetof';


/*
 * Actions
 */

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Action submit/delete file/link
    include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}



/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans('OFAsset');
$helpurl = '';
$shortlabel = dol_trunc($object->ref,16);


llxHeader('', $title, $helpurl);


if ($object->id)
{
   print dol_get_fiche_head(ofPrepareHead( $object, 'assetOF') , 'document', $langs->trans('OFAsset'), -1);

    of_banner($object);

$parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    // Build file list
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);


    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }




    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize,1,1).'</td></tr>';
    print '</table>';

    print '</div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();

    $permission = $user->rights->of->of->write;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';



}
else
{
    print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
