<?php

//ini_set('memory_limit','512M');
require('config.php');

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once(__DIR__.'/class/ordre_fabrication_asset.class.php');
require_once(__DIR__.'/class/oftools.class.php');

if(!$user->rights->of->of->lire) accessforbidden();

$langs->load('of@of');
$langs->load('workstationatm@workstationatm');
$langs->load('stocks');

$PDOdb = new TPDOdb;
if ($conf->workstation->enabled && !class_exists('TWorkstation')) dol_include_once('workstation/class/workstation.class.php');
$TCacheWorkstation = TWorkstation::getWorstations($PDOdb);

$action     = GETPOST('action', 'alpha');
$toselect = GETPOST('toselect', 'array');
$cancel     = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'OFList';   // To manage different context of search
$massaction = GETPOST('massaction', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$sortfield2 = GETPOST('sortfield2', 'alpha');
$sortorder2 = GETPOST('sortorder2', 'alpha');
$page = GETPOSTISSET('int');
$optioncss = GETPOST('optioncss', 'alpha');
$now = dol_now();

$mode = GETPOST('mode', 'alpha');
$oldRank = GETPOST('old_of_rank', 'alpha');
$newRank = GETPOST('of_rank', 'alpha');
$fk_soc = GETPOST('fk_soc', 'int');
$fk_product = GETPOST('fk_product', 'int');
$fk_commande = GETPOST('fk_commande', 'int');
$fk_projet = GETPOST('fk_projet', 'int');
$search_company = GETPOST("search_company", 'alpha');
$search_product = GETPOST("search_product", 'alpha');
$search_order = GETPOST("search_order", 'alpha');
$search_num_of = GETPOST("search_num_of", 'alpha');
$search_status_of = GETPOST("search_status_of", 'alpha');
$search_workstation = GETPOST("search_workstation", 'int');
$search_date_lancement_start = dol_mktime(0, 0, 0, GETPOST('search_date_lancement_startmonth', 'int'), GETPOST('search_date_lancement_startday', 'int'), GETPOST('search_date_lancement_startyear', 'int'));
$search_date_lancement_end = dol_mktime(23, 59, 59, GETPOST('search_date_lancement_endmonth', 'int'), GETPOST('search_date_lancement_endday', 'int'), GETPOST('search_date_lancement_endyear', 'int'));
$search_date_besoin_start = dol_mktime(0, 0, 0, GETPOST('search_date_besoin_startmonth', 'int'), GETPOST('search_date_besoin_startday', 'int'), GETPOST('search_date_besoin_startyear', 'int'));
$search_date_besoin_end = dol_mktime(23, 59, 59, GETPOST('search_date_besoin_endmonth', 'int'), GETPOST('search_date_besoin_endday', 'int'), GETPOST('search_date_besoin_endyear', 'int'));
$search_date_fin_start = dol_mktime(0, 0, 0, GETPOST('search_date_fin_startmonth', 'int'), GETPOST('search_date_fin_startday', 'int'), GETPOST('search_date_fin_startyear', 'int'));
$search_date_fin_end = dol_mktime(23, 59, 59, GETPOST('search_date_fin_endmonth', 'int'), GETPOST('search_date_fin_endday', 'int'), GETPOST('search_date_fin_endyear', 'int'));

if(empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
    $sortfield = "ofe.rowid";
}
if (!$sortorder) {
    $sortorder = "DESC";
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = '';
$form = new Form($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('listof'));

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'ofe.numero' => "OfNumber"
    , 'ofe.date_lancement' => "DateStart"
    , 'ofe.date_besoin' => "DateNeeded"
    , 'ofe.status' => "Status"
    , 'ofe.date_end' => "DateEnd"
);

$arrayfields = array(
    'ofe.numero'=>array('label'=>$langs->trans("OfNumber"), 'checked'=>1),
    'ofel.qty'=>array('label'=>$langs->trans("NumberProductToMake"), 'checked'=>1),
    'ofe.fk_commande'=>array('label'=>$langs->trans("CustomerOrder"), 'checked'=>1),
    'ofe.ordre'=>array('label'=>$langs->trans("Rank"), 'checked'=>1),
    'ofe.date_lancement'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1),
    'ofe.date_besoin'=>array('label'=>$langs->trans("DateNeeded"), 'checked'=>1),
    'ofe.status'=>array('label'=>$langs->trans("Status"), 'checked'=>1),
    'p.label'=>array('label'=>$langs->trans("Product"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("Customer"), 'checked'=>1),
    'ofe.temps_estime_fabrication'=>array('label'=>$langs->trans("EstimatedMakeTimeInHours"), 'checked'=>1),
    'ofe.total_cost'=>array('label'=>$langs->trans("RealCost"), 'checked'=>1),
    'ofe.total_estimated_cost'=>array('label'=>$langs->trans("EstimatedCost"), 'checked'=>1),
    'ofe.fk_project'=>array('label'=>$langs->trans("Project"), 'checked'=>1),
    'ofe.date_end'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'cd.total_ht'=>array('label'=>$langs->trans("OrderLinePrice"), 'checked'=>1)
);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    $objectclass = "OFList";
    $uploaddir = $conf->of->multidir_output; // define only because core/actions_massactions.inc.php want it
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'))
    {
        $sall = "";
        $search_company = "";
        $search_product = "";
        $search_order = "";
        $search_num_of = "";
        $search_status_of = "";
        $search_workstation = "";
        $search_date_lancement_start = "";
        $search_date_lancement_end = "";
        $search_date_besoin_start = "";
        $search_date_besoin_end = "";
        $search_date_fin_start = "";
        $search_date_fin_end = "";
    }

    switch ($action)
    {
        case 'createOFCommande':
            @set_time_limit(0);
            OFTools::_createOFCommande($PDOdb, $_REQUEST['TProducts'], $_REQUEST['TQuantites'], $_REQUEST['fk_commande'], $_REQUEST['fk_soc'], isset($_REQUEST['subFormAlone']));
            break;
        case 'setRank':
            OFTools::_setAllRank($PDOdb, $newRank, $oldRank);
            break;
        case 'printTicket':
            OFTools::_printTicket($PDOdb);
        default:
            set_time_limit(0);
            break;
    }
}

/*
 * View
 */

$title = $langs->trans("ListOFAsset");
$page_name = $langs->trans("ListOFAsset");

$form = new Form($db);
$formother = new FormOther($db);
$companystatic = new Societe($db);
$productstatic = new Product($db);
$assetOf = new TAssetOF;

llxHeader('', $title);

if ($fk_product > 0) {
    $product = new Product($db);
    $result = $product->fetch($fk_product);

    $head = product_prepare_head($product, $user);
    $titre = $langs->trans("CardProduct" . $product->type);
    $picto = ($product->type == 1 ? 'service' : 'product');
    dol_fiche_head($head, 'tabOF2', $titre, -1, $picto);
} elseif ($fk_commande > 0) {

    $commande = new Commande($db);
    $result = $commande->fetch($fk_commande);

    if ($result <= 0) accessforbidden($langs->trans('CannotLoadThisOrderAreYouInTheGoodEntity'), 0);

    $head = commande_prepare_head($commande, $user);
    $titre = $langs->trans("CustomerOrder");
    dol_fiche_head($head, 'tabOF3', $titre, -1, "order");
}

// Build and execute select
$sql = "SELECT ";

if ($mode == 'supplier_order') {
    $sql .= " cf.rowid as supplierOrderId,cf.date_livraison, ofe.rowid, co.ref as reforder, GROUP_CONCAT(DISTINCT ofe.numero SEPARATOR ',') as numero, ofe.fk_soc, s.rowid as socid, s.nom as client, SUM(ofel.qty) as nb_product_to_make
		, GROUP_CONCAT(DISTINCT ofel.fk_product SEPARATOR ',') as fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
        , ofe.fk_commande,ofe.fk_project
		, ofe.status, ofe.fk_user
		,temps_estime_fabrication
		,total_estimated_cost, total_cost
		, '' AS printTicket ";
    if (! empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) $sql .= ', ofe.rank';
} else {
    $sql .= " ofe.rowid,ofel.fk_commandedet, ofe.numero, ofe.fk_soc, co.ref as reforder, s.rowid as socid, s.nom as client, SUM(ofel.qty) as nb_product_to_make
		, GROUP_CONCAT(DISTINCT ofel.fk_product SEPARATOR ',') as fk_product, p.label as product, ofe.ordre
        " . (empty($conf->global->OF_SHOW_WS_IN_LIST) ? '' : ", GROUP_CONCAT(DISTINCT wof.fk_asset_workstation SEPARATOR ',') as fk_asset_workstation") . "
        , ofe.date_lancement
        , ofe.date_besoin
        , ofe.date_end";

    if (! empty($conf->global->OF_MANAGE_ORDER_LINK_BY_LINE)) {
        $sql .= ", GROUP_CONCAT(DISTINCT cd.fk_commande SEPARATOR ',') as fk_commande";
    } else $sql .= ", ofe.fk_commande";

    if (! empty($conf->global->OF_SHOW_ORDER_LINE_PRICE)) {

        $sql .= " ,cd.total_ht as 'order_line_price' ";
    }

    $sql .= ",ofe.fk_project
		, ofe.status, ofe.fk_user
		,temps_estime_fabrication
		,total_estimated_cost, total_cost
		, '' AS printTicket  ";

    if (! empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) $sql .= ', ofe.rank';
}

// Add fields from hooks
$parameters = array(
    'listname' => 'OrderOFList',
    'mode' => $mode
);
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($mode == 'supplier_order') {
    $sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur cf
		  INNER JOIN " . MAIN_DB_PREFIX . "element_element ee ON (ee.fk_target=cf.rowid AND ee.sourcetype='ordre_fabrication' AND targettype='order_supplier' )
		  INNER JOIN " . MAIN_DB_PREFIX . "assetOf as ofe ON (ofe.rowid=ee.fk_source)
		  LEFT JOIN " . MAIN_DB_PREFIX . "assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'TO_MAKE')
		  LEFT JOIN " . MAIN_DB_PREFIX . "product p ON (p.rowid = ofel.fk_product)
		  LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON (s.rowid = ofe.fk_soc)";
} else {
    $sql .= " FROM " . MAIN_DB_PREFIX . "assetOf as ofe
		  LEFT JOIN " . MAIN_DB_PREFIX . "assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'TO_MAKE')
            " . (empty($conf->global->OF_SHOW_WS_IN_LIST) ? '' : " LEFT JOIN " . MAIN_DB_PREFIX . "asset_workstation_of wof ON (wof.fk_assetOf=ofe.rowid) ") . "
		  LEFT JOIN " . MAIN_DB_PREFIX . "product p ON (p.rowid = ofel.fk_product)
		  LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON (s.rowid = ofe.fk_soc)";

    if (! empty($conf->global->OF_SHOW_ORDER_LINE_PRICE) || ! empty($conf->global->OF_MANAGE_ORDER_LINK_BY_LINE)) {

        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet cd ON (cd.rowid=ofel.fk_commandedet) ";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande co ON (co.rowid=cd.fk_commande) ";
    }
    if ($mode == 'non_compliant') {
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'element_element eenc ON (ofe.rowid=eenc.fk_source AND eenc.sourcetype="tassetof" AND eenc.targettype="project_task" )';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task task ON (task.rowid=eenc.fk_target)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_task_extrafields taskext ON (task.rowid=taskext.fk_object)';
    }
}

if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

$sql .= "  WHERE ofe.entity=" . $conf->entity;

// Add where from hooks
$parameters = array(
    'listname' => 'OrderOFList',
    'mode' => $mode
);
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if ($mode == 'non_compliant') {
    if (! empty($conf->global->OF_WORKSTATION_NON_COMPLIANT)) $sql .= " AND taskext.fk_workstation IN (" . $conf->global->OF_WORKSTATION_NON_COMPLIANT . ")";
    else setEventMessage($langs->trans('WarningMustSetConfOF_WORKSTATION_NON_COMPLIANT'), 'warnings');
}
if ($fk_soc > 0) $sql .= " AND ofe.fk_soc=" . $fk_soc;
if ($fk_product > 0) $sql .= " AND ofel.fk_product=" . $fk_product;
if ($fk_commande > 0) {
    if (! empty($conf->global->OF_MANAGE_ORDER_LINK_BY_LINE)) {
        $TLineIds = array();
        if (! empty($commande->lines)) {

            foreach ($commande->lines as $line) $TLineIds[] = $line->id;

            $sql .= " AND ofel.fk_commandedet IN (" . implode(',', $TLineIds) . ") AND ofe.fk_assetOf_parent = 0 ";
        } else $sql .= " AND ofe.fk_commande=" . $fk_commande . " AND ofe.fk_assetOf_parent = 0 ";
    } else $sql .= " AND ofe.fk_commande=" . $fk_commande . " AND ofe.fk_assetOf_parent = 0 ";
}

if ($search_company) {
    $sql .= natural_search('s.nom', $search_company);
}
if ($search_product) {
    $sql .= " AND (p.label LIKE '%".$search_product."%' OR p.ref LIKE '%".$search_product."%')";
}
if ($search_order) {
    $sql .= " AND (co.ref LIKE '%".$search_order."%')";
}
if ($search_num_of) {
    $sql .= natural_search('ofe.numero', $search_num_of);
}
if ($search_status_of != '' && $search_status_of >= 0) {
    $sql .= natural_search('ofe.status', $search_status_of);
}
if ($search_date_lancement_start) {
    $sql .= " AND ofe.date_lancement >= '".$db->idate($search_date_lancement_start)."'";
}
if ($search_date_lancement_end) {
    $sql .= " AND ofe.date_lancement <= '".$db->idate($search_date_lancement_end)."'";
}
if ($search_date_besoin_start) {
    $sql .= " AND ofe.date_besoin >= '".$db->idate($search_date_besoin_start)."'";
}
if ($search_date_besoin_end) {
    $sql .= " AND ofe.date_besoin <= '".$db->idate($search_date_besoin_end)."'";
}
if ($search_date_fin_start) {
    $sql .= " AND ofe.date_end >= '".$db->idate($search_date_fin_start)."'";
}
if ($search_date_fin_end) {
    $sql .= " AND ofe.date_end <= '".$db->idate($search_date_fin_end)."'";
}

if ($mode == 'supplier_order') {
    $sql .= " AND cf.fk_statut IN (2,3,4) ";
    $sql .= " GROUP BY cf.rowid, ofe.rowid ";
} else {
    $sql .= " GROUP BY ofe.rowid ";
}

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);

    if ($result) {
        $nbtotalofrecords = $db->num_rows($result);

        if (($page * $limit) > $nbtotalofrecords)    // if total resultset is smaller then paging size (filtering), goto and load page 0
        {
            $page = 0;
            $offset = 0;
        }
    } else {
        setEventMessage($langs->trans('Error'), 'warnings');
    }
}

$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);
    $arrayofselected = is_array($toselect) ? $toselect : array();

    $param = '';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
    if ($sall) $param .= "&sall=" . urlencode($sall);

    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

    print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';
    print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

    print_barre_liste($langs->trans('ListOFAsset'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);

    include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

    if ($sall)
    {
        foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
    }

    // Filter on categories
    $moreforfilter = '';
    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;

    if ($moreforfilter)
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
    if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    if ($conf->workstationatm->enabled && !class_exists('TWorkstation')) {

        $tmptitle = $langs->trans('Workstations');
        $workstationsUsed = img_picto($tmptitle, 'workstation');
        $workstationsUsed .= ' '.$langs->trans('Workstations');
        $workstationsUsed .= $form->selectarray('search_workstation', $TCacheWorkstation, $search_workstation, 1);
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $workstationsUsed;
        print '</div>';
    }

    // Lines with input filters
    print '<tr class="liste_titre_filter">';

    // Numero
    if (!empty($arrayfields['ofe.numero']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_num_of" value="'.dol_escape_htmltag($search_num_of).'">';
        print '</td>';
    }
    // Client
    if (!empty($arrayfields['s.nom']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="8" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
        print '</td>';
    }
    // Nombre de produits à fabriquer
    if (!empty($arrayfields['ofel.qty']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Produit
    if (!empty($arrayfields['p.label']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_product" value="'.dol_escape_htmltag($search_product).'">';
        print '</td>';
    }
    // Ordre
    if (!empty($arrayfields['ofe.ordre']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Date début
    if (!empty($arrayfields['ofe.date_lancement']['checked'])) {
        print '<td class="liste_titre">';
        print $langs->trans('From').' ';
        print $form->selectDate($search_date_lancement_start ? $search_date_lancement_start : $search_date_lancement_start, 'search_date_lancement_start', 0, 0, 1);
        print $langs->trans('to').' ';
        print $form->selectDate($search_date_lancement_end ? $search_date_lancement_end : $search_date_lancement_end, 'search_date_lancement_end', 0, 0, 1);
        print '</td>';
    }
    // Date du besoin
    if (!empty($arrayfields['ofe.date_besoin']['checked'])) {
        print '<td class="liste_titre">';
        print $langs->trans('From').' ';
        print $form->selectDate($search_date_besoin_start ? $search_date_besoin_start : $search_date_besoin_start, 'search_date_besoin_start', 0, 0, 1);
        print $langs->trans('to').' ';
        print $form->selectDate($search_date_besoin_end ? $search_date_besoin_end : $search_date_besoin_end, 'search_date_besoin_end', 0, 0, 1);
        print '</td>';
    }
    // Date fin
    if (!empty($arrayfields['ofe.date_end']['checked'])) {
        print '<td class="liste_titre">';
        print $langs->trans('From').' ';
        print $form->selectDate($search_date_fin_start ? $search_date_fin_start : $search_date_fin_start, 'search_date_fin_start', 0, 0, 1);
        print $langs->trans('to').' ';
        print $form->selectDate($search_date_fin_end ? $search_date_fin_end : $search_date_fin_end, 'search_date_fin_end', 0, 0, 1);
        print '</td>';
    }
    // Commande
    if (!empty($arrayfields['ofe.fk_commande']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_order" value="'.dol_escape_htmltag($search_order).'">';
        print '</td>';
    }
    // Projet
    if (!empty($arrayfields['ofe.fk_project']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Status
    if (!empty($arrayfields['ofe.status']['checked'])) {
        print '<td class="liste_titre">';
        print $form->selectarray('search_status_of', $assetOf::$TStatus, $search_status_of, 1, 0, 0, '', 1);
        print '</td>';
    }
    // Temps estimé de fabrication
    if (!empty($arrayfields['ofe.temps_estime_fabrication']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Coût prévu
    if (!empty($arrayfields['ofe.total_estimated_cost']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Coût réel
    if (!empty($arrayfields['ofe.total_cost']['checked'])) {
        print '<td class="liste_titre">';
        print '</td>';
    }

    // Impression étiquette
    print '<td class="liste_titre">';
    print '</td>';

    // Rang
    if (!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) {
        print '<td class="liste_titre">';
        print '</td>';
    }

    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '<td class="liste_titre" align="left">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print '</tr>';

    print '<tr class="liste_titre">';

    if (! empty($arrayfields['ofe.numero']['checked']))  print_liste_field_titre('OfNumber', $_SERVER["PHP_SELF"], "ofe.numero", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['s.nom']['checked']))  print_liste_field_titre('Customer', $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofel.qty']['checked']))  print_liste_field_titre('NumberProductToMake', $_SERVER["PHP_SELF"], "nb_product_to_make", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['p.label']['checked']))  print_liste_field_titre('Product', $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.ordre']['checked']))  print_liste_field_titre('Priority', $_SERVER["PHP_SELF"], "ofe.ordre", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.date_lancement']['checked']))  print_liste_field_titre('DateStart', $_SERVER["PHP_SELF"], "ofe.date_lancement", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.date_besoin']['checked']))  print_liste_field_titre('DateNeeded', $_SERVER["PHP_SELF"], "ofe.date_besoin", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.date_end']['checked']))  print_liste_field_titre('DateEnd', $_SERVER["PHP_SELF"], "ofe.date_end", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.fk_commande']['checked']))  print_liste_field_titre('CustomerOrder', $_SERVER["PHP_SELF"], "ofe.fk_commande", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.fk_project']['checked']))  print_liste_field_titre('Project', $_SERVER["PHP_SELF"], "ofe.fk_project", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.status']['checked']))  print_liste_field_titre('Status', $_SERVER["PHP_SELF"], "ofe.status", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.temps_estime_fabrication']['checked']))  print_liste_field_titre('EstimatedMakeTimeInHours', $_SERVER["PHP_SELF"], "ofe.temps_estime_fabrication", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.total_estimated_cost']['checked']))  print_liste_field_titre('EstimatedCost', $_SERVER["PHP_SELF"], "ofe.total_estimated_cost", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['ofe.total_cost']['checked']))  print_liste_field_titre('RealCost', $_SERVER["PHP_SELF"], "ofe.total_cost", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre('ofPrintTicket', $_SERVER["PHP_SELF"], "printTicket", "", $param, "", $sortfield, $sortorder);
    if (!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE))  print_liste_field_titre('Rank', $_SERVER["PHP_SELF"], "ofe.rank", "", $param, "", $sortfield, $sortorder);

    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
    $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
    print "</tr>\n";

    $i = 0;
    $totalarray=array();

    while($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';

        // Numero
        if (!empty($arrayfields['ofe.numero']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_format_link_of($obj->numero, $obj->rowid);
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
        }
        // Client
        if (!empty($arrayfields['s.nom']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_format_libelle_societe($obj->fk_soc);
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
        }
        // Nombre de produits à fabriquer
        if (!empty($arrayfields['ofel.qty']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print $obj->nb_product_to_make;
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
        }
        // Produit
        if (!empty($arrayfields['p.label']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_format_libelle_produit($obj->fk_product);
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
        }
        // Ordre
        if (!empty($arrayfields['ofe.ordre']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print $obj->ordre;
            print '</td>';
            if(! $i) $totalarray['nbfield']++;
        }
        // Date début
        if (!empty($arrayfields['ofe.date_lancement']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print dol_print_date($db->jdate($obj->date_lancement), 'day', 'tzuser');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Date du besoin
        if (!empty($arrayfields['ofe.date_besoin']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print dol_print_date($db->jdate($obj->date_besoin), 'day', 'tzuser');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Date fin
        if (!empty($arrayfields['ofe.date_end']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print dol_print_date($db->jdate($obj->date_end), 'day', 'tzuser');
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Commande
        if (!empty($arrayfields['ofe.fk_commande']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_format_libelle_commande($obj->fk_commande, $obj->fk_commande_det, $obj->fk_product);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Projet
        if (!empty($arrayfields['ofe.fk_project']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_format_libelle_projet($obj->fk_project);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Status
        if (!empty($arrayfields['ofe.status']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print TAssetOF::status($obj->status, true);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Temps estimé de fabrication
        if (!empty($arrayfields['ofe.temps_estime_fabrication']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print round($obj->temps_estime_fabrication, 2);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Coût prévu
        if (!empty($arrayfields['ofe.total_estimated_cost']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print round($obj->total_estimated_cost, 2);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Coût réel
        if (!empty($arrayfields['ofe.total_cost']['checked'])) {
            print '<td class="tdoverflowmax200">';
            print round($obj->total_cost, 2);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Impression étiquette
        print '<td class="tdoverflowmax200">';
        print '<input style=width:40px;"" type="number" value="'.((int) $conf->global->OF_NB_TICKET_PER_PAGE).'" name="printTicket['.$obj->rowid.']" min="0" />';
        print "</td>\n";
        if(! $i) $totalarray['nbfield']++;

        // Rang
        if (!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) {
            print '<td class="tdoverflowmax200">';
            print OFTools::get_number_input("of_rank[$obj->rowid]", $obj->rank);
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
        }

        // Extra fields
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';
        // Fields from hook
        $parameters = ['arrayfields' => $arrayfields, 'obj' => $obj];
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        // Action
        print '<td class="nowrap" align="center">';
        if($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
        {
            $selected = 0;
            if(in_array($obj->rowid, $arrayofselected)) $selected = 1;
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=updateRate&amp;id_rate='.$obj->rowid.'" class="like-link " style="margin-right:15px;important">'.img_picto('edit', 'edit').'</a>';
            print '<a href="'.$_SERVER["PHP_SELF"].'?action=deleteRate&amp;id_rate='.$obj->rowid.'" class="like-link" style="margin-right:45px;important">'.img_picto('delete', 'delete').'</a>';
            print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
        }
        print '</td>';

        if(! $i) $totalarray['nbfield']++;

        print "</tr>\n";
        $i++;
    }


    // Show total line
    include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

    $db->free($resql);

    print "</table>";
    print "</div>";

    if ($conf->global->OF_NB_TICKET_PER_PAGE != -1) {
        echo '<p align="right"><input class="button" type="button" onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'printTicket\');  $(this).closest(\'form\').submit(); " name="print" value="'.$langs->trans('ofPrintTicket').'" /></p>';
    }
    if(!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) {
        echo '<p align="right"><input id="bt_updateRank" class="button" onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'setRank\');" type="submit" value="'.$langs->trans('UpdateRank').'"/></p>';
    }

    print '</form>';

    // On n'affiche pas le bouton de création d'OF si on est sur la liste OF depuis l'onglet "OF" de la fiche commande
    if($fk_commande>0)
    {
        $commande=new Commande($db);
        $commande->fetch($fk_commande);

        $sql = "SELECT c.rowid as fk_commandedet, p.rowid as rowid, p.ref as refProd, p.label as nomProd, c.qty as qteCommandee, c.description, c.label as lineLabel, c.product_type";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet c LEFT JOIN ".MAIN_DB_PREFIX."product p";
        $sql.= " ON (c.fk_product = p.rowid)";
        $sql.= " WHERE c.product_type IN (0,9) AND  c.fk_commande = ".$fk_commande;
        $sql.= " ORDER BY c.rang";

        $resql = $db->query($sql);
        $num = $db->num_rows($resql);

        print_barre_liste($langs->trans('ListOrderProducts'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);

        $i = 0;

        $form=new TFormCore($_SERVER['PHP_SELF'], 'formMakeOk', 'post');
        echo $form->hidden('fk_commande', __get('fk_commande',0,'int'));
        echo $form->hidden('action', 'createOFCommande');
        echo $form->hidden('fk_soc', $commande->socid);
        echo $form->hidden('token', $_SESSION['newtoken']);

        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print_liste_field_titre("#");
        print_liste_field_titre($langs->trans("Ref"),"liste_of.php","ref","",$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Label"),"liste_of.php","label", "", $param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("PhysicalStock"),"liste_of.php","", "", $param,'align="left"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('QtyAlreadyToMake'),"liste_of.php","","",$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('QtyToMake'),"liste_of.php","","",$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('ProductToAddToOf'),"liste_of.php","","",$param,'',$sortfield,$sortorder);
        print "</tr>\n";
        $var=1;

        $bc = array(1=>'class="pair"',-1=>'class="impair"');

        while ($prod = $db->fetch_object($resql))
        {
            $var=!$var;

            $parameters = array('commande' => $commande, 'var' => $var, 'i' => $i);
            $reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $prod, $action);    // Note that $action and $object may have been modified by some hooks

            if (empty($reshook))
            {
                if($prod->product_type == 9 && !empty($conf->subtotal->enabled)) {
                    print "<tr>";
                    print "<td>&nbsp;</td>";
                    print '<td colspan="6" '.($prod->qteCommandee>50 ? 'style="text-align:right; padding-right:'.((100 - $prod->qteCommandee)*10).'px;"' : 'style="text-align:left; padding-left:'.(($prod->qteCommandee)*10).'px;"').'><strong>';
                    print empty($prod->description) ? $prod->lineLabel: $prod->description;
                    print '</strong></td>';
                }
                else if(empty($prod->rowid)) {
                    // ligne libre
                    print "<tr>";
                    print "<td>&nbsp;</td>";

                    print "<td colspan=\"4\">";
                    print $prod->description;
                    print '</td>';

                }
                else {

                    print "<tr ".$bc[$var].">";
                    print "<td>".($i+1)."</td>";

                    print "<td>";
                    $p_static = new Product($db);
                    $p_static->fetch($prod->rowid);
                    $p_static->load_stock();
                    $p_static->ref = $prod->refProd;
                    $p_static->id = $prod->rowid;
                    print $p_static->getNomUrl(1);
                    print "</td>\n";
                    print '<td>';
                    print $prod->nomProd;

                    if(!empty($conf->{ ATM_ASSET_NAME }->enabled) && !empty($conf->global->USE_ASSET_IN_ORDER)) {
                        $line = new OrderLine($db);
                        $line->fetch($prod->fk_commandedet);
                        $line->fetch_optionals($prod->fk_commandedet);

                        echo '<input type="hidden" name="TAsset['.$prod->fk_commandedet.']" value="'.(int)$line->array_options['options_fk_asset'].'" >';
                        if($line->array_options['options_fk_asset']>0) {
                            dol_include_once('/' . ATM_ASSET_NAME . '/class/asset.class.php');

                            $asset=new TAsset();
                            $asset->load($PDOdb, $line->array_options['options_fk_asset']);

                            echo ' '.$asset->getNomUrl(true,true,true);
                        }

                    }

                    print '</td>';
                    print '<td>';
                    print $p_static->stock_reel;
                    print '</td>';
                    $sqlOf = "SELECT SUM(ofl.qty) as qty FROM ".MAIN_DB_PREFIX."assetOf_line ofl
						INNER JOIN ".MAIN_DB_PREFIX."assetOf of ON (of.rowid=ofl.fk_assetOf) WHERE ";
                    if(empty($conf->global->OF_MANAGE_ORDER_LINK_BY_LINE)) $sqlOf .=" of.fk_commande=".$fk_commande." AND";
                    $sqlOf .=" ofl.type='TO_MAKE' AND ofl.fk_commandedet=".$prod->fk_commandedet;
                    $resOf = $db->query($sqlOf);

                    $objof = $db->fetch_object($resOf);
                    $qtyInOF = $objof->qty;

                    print "<td>";
                    print $qtyInOF;
                    print "</td>";



                    $qtyToMake = $prod->qteCommandee - $qtyInOF;

                    print "<td>";
                    print $form->texte('','TQuantites['.$prod->fk_commandedet.']', $qtyToMake>0 ? $qtyToMake : 0,3,255);
                    print "</td>";

                    print "<td>".$form->checkbox1('', 'TProducts['.$prod->fk_commandedet.']['.(int)$prod->rowid.']', false, $qtyToMake>0 ,'','checkOF' );
                    print "</td>";


                    print "</tr>\n";
                    $i++;

                }
            }

        }

        print '<tr class="liste_titre">';
        echo '<th class="liste_titre" colspan="4">&nbsp;</th><th class="liste_titre">&nbsp;</th><th class="liste_titre">&nbsp;</th>
		<th class="liste_titre"><input type="checkbox" id="checkall" checked="checked" value="1"></th>
		';
        print '</tr>';

        print "</table>";

        ?><script type="text/javascript">
        $('input#checkall').change(function() {

            $('input.checkOF').prop('checked',$(this).is(':checked'));

        });

    </script>

        <?php

        echo '<p align="right">'.$form->btsubmit($langs->trans('CreateAnyOf'), 'subForm')
            .' '.$form->btsubmit($langs->trans('CreateOnceOf'), 'subFormAlone').'</p>';

        $form->end();

        echo '</div>';


        $db->free($resql);


    }
    else
    {
        if(!empty($fk_product))
        {
            $sql = "SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(IF(ofel.qty>0,ofel.qty,ofel.qty_needed) ) as nb_product_needed, ofel.fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
            , ofe.status, ofe.fk_user, ofe.total_cost ";

            $sql.= " FROM ".MAIN_DB_PREFIX."assetOf as ofe
              LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'NEEDED')
              LEFT JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = ofel.fk_product)
              LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = ofe.fk_soc) ";

            $sql.= " WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$fk_product." AND ofe.status!='CLOSE'";

            $sql.= " GROUP BY ofe.rowid ";

            if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
            else $orderBy['ofe.date_cre']='DESC';

            $TMath=array();
            $THide = array('rowid','fk_user','fk_product','fk_soc');
            if(empty($user->rights->{ ATM_ASSET_NAME }->of->price)) $THide[] = 'total_cost';
            else $TMath['total_cost']='sum';

            $TMath['nb_product_needed']='sum';

            $resql = $db->query($sql);
            $num = $db->num_rows($resql);

            print_barre_liste($langs->trans('ofListProductNeeded'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);

            $i = 0;

            print '<table class="noborder" width="100%">';

            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans("OfNumber"),"liste_of_rework.php","ofe.numero","",$param,'',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans("Customer"),"liste_of_rework.php","s.nom", "", "",'', $sortfield2, $sortorder2);
            print_liste_field_titre($langs->trans("NumberProductToMake"),"liste_of_rework.php","nb_product_needed", "", $param,'align="left"',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans('Product'),"liste_of_rework.php","p.label","",$param,'',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans('Priority'),"liste_of_rework.php","ofe.ordre","",$param,'',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans('DateStart'),"liste_of_rework.php","ofe.date_lancement","",$param,'',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans('DateNeeded'),"liste_of_rework.php","ofe.date_besoin","",$param,'',$sortfield2,$sortorder2);
            print_liste_field_titre($langs->trans('Status'),"liste_of_rework.php","ofe.status","",$param,'',$sortfield2,$sortorder2);
            print "</tr>\n";
            $var=1;

            $bc = array(1=>'class="pair"',-1=>'class="impair"');

            if ($resql){
                while ($ofProductNeeded = $db->fetch_object($resql))
                {
                    $var=!$var;
                    if (empty($reshook)) {

                        print "<tr " . $bc[$var] . ">";
                        print '<td>';
                        print OFTools::get_format_link_of($ofProductNeeded->numero, $ofProductNeeded->rowid);
                        print '</td>';

                        print "<td>";
                        print OFTools::get_format_libelle_societe($ofProductNeeded->fk_soc);
                        print "</td>";

                        print "<td>";
                        print $ofProductNeeded->nb_product_needed;
                        print "</td>";

                        print "<td>";
                        print OFTools::get_format_libelle_produit($ofProductNeeded->fk_product);
                        print "</td>";

                        print "<td>";
                        print $ofProductNeeded->ordre;
                        print "</td>";

                        print "<td>";
                        print dol_print_date($db->jdate($ofProductNeeded->date_lancement), 'day', 'tzuser');
                        print "</td>";

                        print "<td>";
                        print dol_print_date($db->jdate($ofProductNeeded->date_besoin), 'day', 'tzuser');
                        print "</td>";

                        print "<td>";
                        print TAssetOF::status($ofProductNeeded->status, true);
                        print "</td>";

                        print "</tr>\n";
                        $i++;
                    }
                }
            }

            print '<tr class="liste_titre">';
            echo '<th class="liste_titre" colspan="6">&nbsp;</th><th class="liste_titre">&nbsp;</th><th class="liste_titre">&nbsp;</th>
            ';
            print '</tr>';

            print "</table>";

        }

        echo '<div class="tabsAction">';
        echo '<a id="bt_createOf" class="butAction" href="fiche_of.php?action=new'.((!empty($fk_product)) ? '&fk_product='.$fk_product : '' ).'">'.$langs->trans('CreateOFAsset').'</a>';
        if ($conf->nomenclature->enabled && !empty($fk_product))
        {
            dol_include_once('/core/class/html.form.class.php');
            dol_include_once('/' . ATM_ASSET_NAME . '/lib/asset.lib.php');
            dol_include_once('/nomenclature/class/nomenclature.class.php');

            $doliForm = new Form($db);
            echo $doliForm->selectarray('fk_nomenclature', TNomenclature::get($PDOdb, $fk_product, true));

            echo '<script type="text/javascript">

				    var url_create_of = $("#bt_createOf").attr("href");
	                   	    $("#bt_createOf").attr("href","#");

					$("#bt_createOf").click(function() {
						var fk_nomenclature = $("select[name=fk_nomenclature]").val();
						var href = url_create_of + "&fk_nomenclature=" + fk_nomenclature;
						$(this).attr("href", href);
					});
			</script>';

        }

        echo '</div>';

    }
}
else {
    dol_print_error($db);
}


llxFooter();
$db->close();