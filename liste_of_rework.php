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

if(!$user->rights->of->of->lire) accessforbidden();

$langs->load('of@of');
$langs->load('workstationatm@workstationatm');
$langs->load('stocks');

$PDOdb = new TPDOdb;

$action     = GETPOST('action', 'alpha');
$toselect = GETPOST('toselect', 'array');
$cancel     = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'easycommissionList';   // To manage different context of search
$massaction = GETPOST('massaction', 'alpha');
$search_ref_exp = GETPOST("search_ref_exp", 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('int');
$optioncss = GETPOST('optioncss', 'alpha');
$now = dol_now();

$mode = GETPOST('mode', 'none');
$fk_soc = GETPOST('fk_soc', 'int');
$fk_product = GETPOST('fk_product', 'int');
$fk_commande = GETPOST('fk_commande', 'int');
$search_num_of = GETPOST("search_num_of", 'alpha');
$search_status_of = GETPOST("search_status_of", 'alpha');
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
    $sortfield = "e.ref";
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
    , 'wof.fk_asset_workstation' => "Workstations"
);

$arrayfields = array(
    'ofe.numero'=>array('label'=>$langs->trans("OfNumber"), 'checked'=>1),
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
    'cf.rowid'=>array('label'=>$langs->trans("AssetProductionSupplierOrder"), 'checked'=>1),
    'cf.date_livraison'=>array('label'=>$langs->trans("DeliveryDate"), 'checked'=>1),
    'ofe.date_end'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'ofe.rank'=>array('label'=>$langs->trans("Rank"), 'checked'=>1),
    'wof.fk_asset_workstation'=>array('label'=>$langs->trans("Workstations"), 'checked'=>1),
    'cd.total_ht'=>array('label'=>$langs->trans("OrderLinePrice"), 'checked'=>1)
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

if ($fk_product > 0) {
    $product = new Product($db);
    $result = $product->fetch($fk_product);

    $head = product_prepare_head($product, $user);
    $titre = $langs->trans("CardProduct" . $product->type);
    $picto = ($product->type == 1 ? 'service' : 'product');
    dol_fiche_head($head, 'tabOF2', $titre, -1, $picto);
} elseif ($fk_commande > 0) {
    dol_include_once("/core/lib/order.lib.php");

    $commande = new Commande($db);
    $result = $commande->fetch($fk_commande);

    if ($result <= 0) {

        accessforbidden($langs->trans('CannotLoadThisOrderAreYouInTheGoodEntity'), 0);
    }

    $head = commande_prepare_head($commande, $user);
    $titre = $langs->trans("CustomerOrder");
    dol_fiche_head($head, 'tabOF3', $titre, -1, "order");
}

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'))
    {
        $sall = "";
        $search_num_of = "";
        $search_status_of = "";
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
            _createOFCommande($PDOdb, $_REQUEST['TProducts'], $_REQUEST['TQuantites'], $_REQUEST['fk_commande'], $_REQUEST['fk_soc'], isset($_REQUEST['subFormAlone']));
            _liste($PDOdb);
            break;
        case 'setRank':
            _setAllRank($PDOdb, GETPOST('of_rank', 'none'), GETPOST('old_of_rank', 'none'));
            _liste($PDOdb);
            break;
        case 'printTicket':
            _printTicket($PDOdb);
        default:
            set_time_limit(0);
            _liste($PDOdb);
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
$formfile = new FormFile($db);
$companystatic = new Societe($db);
$formcompany = new FormCompany($db);
$assetOf = new TAssetOF;

llxHeader('', $title);

// Subheader
print load_fiche_titre($langs->trans($page_name));

// Build and execute select
$sql = "SELECT ";

if ($mode == 'supplier_order') {
    $sql .= " cf.rowid as supplierOrderId,cf.date_livraison, ofe.rowid, GROUP_CONCAT(DISTINCT ofe.numero SEPARATOR ',') as numero, ofe.fk_soc, s.nom as client, SUM(ofel.qty) as nb_product_to_make
		, GROUP_CONCAT(DISTINCT ofel.fk_product SEPARATOR ',') as fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
        , ofe.fk_commande,ofe.fk_project
		, ofe.status, ofe.fk_user
		,temps_estime_fabrication
		,total_estimated_cost, total_cost
		, '' AS printTicket ";
    if (! empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) $sql .= ', ofe.rank';
} else {
    $sql .= " ofe.rowid,ofel.fk_commandedet, ofe.numero, ofe.fk_soc, s.nom as client, SUM(ofel.qty) as nb_product_to_make
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
if ($search_num_of) {
    $sql .= natural_search('ofe.numero', $search_num_of);
}
if ($search_status_of) {
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
if (! empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) {
    $sql .= " ORDER BY ofe.date_lancement ASC, ofe.rank ASC, ofe.rowid DESC ";
} else $sql .= " ORDER BY ofe.rowid DESC ";


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

    // Lines with input filters
    print '<tr class="liste_titre_filter">';

    // Numero
    if (!empty($arrayfields['ofe.numero']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_num_of" value="'.$search_num_of.'">';
        print '</td>';
    }

    if (! empty($arrayfields['ofe.numero']['checked']))	print '<td class="liste_titre" align="left"></td>';
    if (! empty($arrayfields['det.total_ht']['checked'])) print '<td class="liste_titre" align="left"></td>';
    if (! empty($arrayfields['det.remise_percent']['checked']))	print '<td class="liste_titre" align="left"></td>';
    if (! empty($arrayfields['det.fk_product']['checked']))	print '<td class="liste_titre" align="left"></td>';

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
    if ( ! empty($search_sale)) {
        print_liste_field_titre('EasyComSocAndCommercial', $_SERVER["PHP_SELF"], "fa.fk_soc", "", $param, "", $sortfield, $sortorder);
        if (! empty($arrayfields['fa.ref']['checked']))  print_liste_field_titre($arrayfields['fa.ref']['label'], $_SERVER["PHP_SELF"], "fa.ref", "", $param, "", $sortfield, $sortorder);
        if (! empty($arrayfields['det.fk_product']['checked']))  print_liste_field_titre($arrayfields['det.fk_product']['label'], $_SERVER["PHP_SELF"], "det.fk_product", "", $param, "", $sortfield, $sortorder);
        if (! empty($arrayfields['det.total_ht']['checked']))  print_liste_field_titre($arrayfields['det.total_ht']['label'], $_SERVER["PHP_SELF"], "det.total_ht", "", $param, "align='right'", $sortfield, $sortorder);
        if (! empty($arrayfields['det.remise_percent']['checked']))  print_liste_field_titre($arrayfields['det.remise_percent']['label'], $_SERVER["PHP_SELF"], "det.remise_percent", "", $param, "align='right'", $sortfield, $sortorder);
        print_liste_field_titre('EasyCommissionTitle', '', '', '', '', "align='right'");
    }
    else {
        print_liste_field_titre('EasyCommercial', $_SERVER["PHP_SELF"], "fa.fk_soc", "", $param, "", $sortfield, $sortorder);
        if (! empty($arrayfields['fa.ref']['checked']))  print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", '', "", '', '');
        if (! empty($arrayfields['det.fk_product']['checked']))  print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", "", "", "", "");
        if (! empty($arrayfields['det.remise_percent']['checked']))  print_liste_field_titre("", $_SERVER["PHP_SELF"], "", "", "", "align='right'", "", "");
        if (! empty($arrayfields['det.total_ht']['checked']))  print_liste_field_titre($arrayfields['det.total_ht']['label'], $_SERVER["PHP_SELF"], "det.total_ht", "", $param, "align='right'", $sortfield, $sortorder);
        print_liste_field_titre('EasyCommissionTitle', '', '', '', '', "align='right'");
    }


    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
    $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
    print "</tr>\n";

    $i = 0;
    $totalarray=array();

    if (empty($search_sale)) {
        // Affichage des lignes de totaux HT et Commissions par utilisateur
        EasyCommissionTools::displayTotauxByCommercial($TUsersTotaux, $i, $arrayfields, $totalarray);
    }
    else {
        // Affichage du détail des lignes de facture pour le commercial sélectionné
        while($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);

            $TRes = EasyCommissionTools::calcul_com($obj, $TCom, $TUserCom);

            print '<tr class="oddeven">';

            // Societe
            $soc = new Societe($db);
            $soc->fetch($obj->srowid);
            $user->fetch($obj->fk_user);

            print '<td class="tdoverflowmax200">';
            print $soc->getNomUrl(1, '', '', 1, '');
            print '</br>';
            print $user->getNomUrl(1, '', '', 1);
            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;


            // Fac REF
            if(! empty($arrayfields['fa.ref']['checked'])) {
                $facturestatic->id = $obj->facrowid;
                $facturestatic->ref = $obj->facref;
                $facturestatic->ref_client = $obj->ref_client;
                $facturestatic->total_ht = $obj->total_ht;
                $facturestatic->total_tva = $obj->total_vat;
                $facturestatic->total_ttc = $obj->total_ttc;

                print '<td class="tdoverflowmax200">';
                print $facturestatic->getNomUrl(1);
                print "</td>\n";
                if(! $i) $totalarray['nbfield']++;
            }

            // Facdet product
            if(! empty($arrayfields['det.fk_product']['checked'])) {
                $productstatic->id = $obj->detproduct;
                $productstatic->ref = $obj->productref;
                $productstatic->label = $obj->productlabel;
                $productstatic->status = $obj->productsell;
                $productstatic->status_buy = $obj->productbuy;

                print '<td class="tdoverflowmax200">';
                print $productstatic->getNomUrl(1);
                print "</td>\n";
                if(! $i) $totalarray['nbfield']++;
            }

            // Facdet total HT
            if(! empty($arrayfields['det.total_ht']['checked'])) {
                print '<td class="tdoverflowmax200" align="right">';
                print round($obj->total_ht, 2);
                print "</td>\n";
                if(! $i) $totalarray['nbfield']++;
                if(! $i) $totalarray['pos'][$totalarray['nbfield']] = 'det.total_ht';
                $totalarray['val']['det.total_ht'] += $obj->total_ht;
            }

            // Facdet remise
            if(! empty($arrayfields['det.remise_percent']['checked'])) {
                print '<td class="tdoverflowmax200" align="right">';
                print $obj->remise_percent.'%';
                print "</td>\n";
                if(! $i) $totalarray['nbfield']++;
            }

            // Facdet Commercial Commission
            print '<td class="tdoverflowmax200" align="right">';
            if(! $TRes['missingInfo']) print price(round($TRes['commission'], 2));
            else print $TRes['missingInfo'];

            print "</td>\n";
            if(! $i) $totalarray['nbfield']++;
            if(! $i) $totalarray['pos'][$totalarray['nbfield']] = 'Commission';
            $totalarray['val']['Commission'] += round($TRes['commission'], 2);

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
    }


    // Show total line
    include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

    $db->free($resql);

    print "</table>";
    print "</div>";

    print '</form>';
}
else {
    dol_print_error($db);
}


llxFooter();
$db->close();

function _liste(&$PDOdb)
{
    global $langs, $db, $user, $conf, $TCacheWorkstation, $hookmanager;



//    $r = new TSSRenderControler($assetOf);
    
    //.....
//    if(!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE))$orderBy=array("date_lancement" => "ASC", "rank"=>"ASC",'rowid'=>'DESC');
//    else $orderBy=array('rowid'=>'DESC');

    $TMath=array();
    $THide = array('rowid','fk_commandedet','fk_user','fk_product','fk_soc');
    if($fk_commande>0)  $THide[] = 'fk_commande';

    if ($conf->global->OF_NB_TICKET_PER_PAGE == -1) $THide[] = 'printTicket';

    if(empty($user->rights->of->of->price)) {
        $THide[] = 'total_cost';
        $THide[] = 'total_estimated_cost';
    }
    else {

        $TMath['total_estimated_cost']='sum';
        $TMath['total_cost']='sum';
        $TMath['temps_estime_fabrication']='sum';
    }

    $PDOdb=new TPDOdb;
    if ($conf->workstation->enabled && !class_exists('TWorkstation')) dol_include_once('workstation/class/workstation.class.php');
    $TCacheWorkstation = TWorkstation::getWorstations($PDOdb);

    $TSearch=array();
    if($mode =='supplier_order') {
        $THide[] = 'date_lancement';
        $THide[] = 'nb_product_to_make';
        //$THide[] = 'status';
        $THide[] = 'ordre';
    }
    else{
        $TSearch=array(
            'numero'=>array('recherche'=>true, 'table'=>'ofe')
        ,'date_lancement'=>array('recherche'=>'calendars', 'table'=>'ofe')
        ,'date_besoin'=>array('recherche'=>'calendars', 'table'=>'ofe')
        ,'status'=>array('recherche'=>TAssetOF::$TStatus, 'table'=>'ofe', 'to_translate' => true)
        ,'date_end'=>array('recherche'=>'calendars','table'=>'ofe')
        ,'fk_asset_workstation'=>array('recherche'=>$TCacheWorkstation, 'table'=>'wof')
        );
        $TStatus = TAssetOF::$TStatus;
        unset($TStatus['CLOSE']);

        $NOTCLOSED = "'".implode("','", array_keys($TStatus))."'";
        $TSearch['status']['recherche'][$NOTCLOSED] = $langs->trans('AllExceptClosed');
    }

    if(!empty($fk_product)) $TMath['nb_product_to_make']='sum';

    if(!empty($conf->global->OF_SHOW_ORDER_LINE_PRICE)) $TMath['order_line_price'] = 'sum';

    $form=new TFormCore($_SERVER['PHP_SELF'], 'form', 'GET');


    $allExceptClose = false;
    if($_REQUEST['TListTBS']['list_llx_assetOf']['search']['status'] == $NOTCLOSED)
    {
        $allExceptClose = true;
    }

    echo $form->hidden('action', '');
    if ($fk_commande > 0) echo $form->hidden('fk_commande', $fk_commande);
    if(!empty($mode)) echo $form->hidden('mode', $mode);
    if($fk_product > 0) echo $form->hidden('fk_product', $fk_product); // permet de garder le filtre produit quand on est sur l'onglet OF d'une fiche produit

    if($mode =='supplier_order') $title = $langs->trans('AssetProductionSupplierOrder');
    else if($mode =='non_compliant') $title = $langs->trans('ListOFAssetNonCompliant');
    else $title = $langs->trans('ListOFAsset');

    $listViewConfig = array(
        'limit'=>array(
            'nbLine'=>$conf->liste_limit
        )
    ,'orderBy'=>$orderBy
    ,'subQuery'=>array()
    ,'link'=>array(
            'Utilisateur en charge'=>'<a href="'.dol_buildpath('/user/card.php?id=@fk_user@', 1).'">'.img_picto('','object_user.png','',0).' @val@</a>'
        ,'printTicket'=>'<input style=width:40px;"" type="number" value="'.((int) $conf->global->OF_NB_TICKET_PER_PAGE).'" name="printTicket[@rowid@]" min="0" />'
        )
    ,'translate'=>array(
            'ordre'=>TAssetOF::$TOrdre
        )
    ,'hide'=>$THide
    ,'type'=>array(
            'date_lancement'=>'date'
        ,'date_besoin'=>'date'
        ,'temps_estime_fabrication'=>'money'
        ,'total_cost'=>'money'
        ,'total_estimated_cost'=>'money'
        ,'nb_product_to_make'=>'number'
        ,'date_livraison'=>'date'
        ,'date_end'=>'date'
        ,'order_line_price'=>'money'
        )
    ,'math'=>$TMath
    ,'liste'=>array(
            'titre'=>$title
        ,'image'=>img_picto('','title.png', '', 0)
        ,'picto_precedent'=>img_picto('','back.png', '', 0)
        ,'picto_suivant'=>img_picto('','next.png', '', 0)
        ,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
        ,'messageNothing'=>$langs->trans('noOfFound')
        ,'picto_search'=>img_picto('','search.png', '', 0)
        )
    ,'title'=>array(
            'numero'=>                   $langs->trans('OfNumber')
        ,'fk_commande'=>             $langs->trans('CustomerOrder')
        ,'ordre'=>                   $langs->trans('Rank')
        ,'date_lancement'=>          $langs->trans('DateStart')
        ,'date_besoin'=>             $langs->trans('DateNeeded')
        ,'status'=>                  $langs->trans('Status')
        ,'login'=>                   $langs->trans('UserAssign')
        ,'product'=>                 $langs->trans('Product')
        ,'client'=>                  $langs->trans('Customer')
        ,'nb_product_to_make'=>      $langs->trans('NumberProductToMake')
        ,'temps_estime_fabrication'=>$langs->trans('EstimatedMakeTimeInHours')
        ,'total_cost'=>              $langs->trans('RealCost')
        ,'total_estimated_cost'=>    $langs->trans('EstimatedCost')
        ,'printTicket' =>            $langs->trans('PrintTicket')
        ,'fk_project'=>              $langs->trans('Project')
        ,'supplierOrderId'=>         $langs->trans('AssetProductionSupplierOrder')
        ,'date_livraison'=>          $langs->trans('DeliveryDate')
        ,'date_end'=>                $langs->trans('DateEnd')
        ,'rank' =>                   $langs->trans('Rank')
        ,'fk_asset_workstation'=>    $langs->trans('Workstations')
        ,'order_line_price'=>        $langs->trans('OrderLinePrice')
        )

    ,'eval'=>array(
            'ordre'=>'TAssetOF::ordre("@val@")'
        ,'status'=>'TAssetOF::status("@val@", true)'
        ,'product' => 'get_format_libelle_produit("@fk_product@")'
        ,'fk_asset_workstation' => 'get_format_label_workstation("@fk_asset_workstation@")'
        ,'client' => 'get_format_libelle_societe(@fk_soc@)'
        ,'fk_commande'=>'get_format_libelle_commande("@fk_commande@","@fk_commandedet@","@fk_product@")'
        ,'fk_project'=>'get_format_libelle_projet(@fk_project@)'
        ,'numero'=>'get_format_link_of("@val@",@rowid@)'
        ,'supplierOrderId'=>'get_format_label_supplier_order(@supplierOrderId@)'
        ,'rank'=>'get_number_input("of_rank[@rowid@]",@rank@)'

        )
    ,'operator'=>array(
            'fk_asset_workstation'=>'='
        ,'status' => $allExceptClose ? 'IN' : "="
        )
    ,'search'=>$TSearch
    );

    // Change view from hooks
    $parameters=array(
        'listViewConfig' => $listViewConfig,
        'listname' => 'OrderOFList'
    );
    $reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    if ($reshook>0)
    {
        $listViewConfig = $hookmanager->resArray;
    }

    $r->liste($PDOdb, $sql, $listViewConfig);

    if ($conf->global->OF_NB_TICKET_PER_PAGE != -1) {
        echo '<p align="right"><input class="button" type="button" onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'printTicket\');  $(this).closest(\'form\').submit(); " name="print" value="'.$langs->trans('ofPrintTicket').'" /></p>';
    }
    if(!empty($conf->global->OF_RANK_PRIOR_BY_LAUNCHING_DATE)) {
        echo '<p align="right"><input id="bt_updateRank" class="button" onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'setRank\');" type="submit" value="'.$langs->trans('UpdateRank').'"/></p>';
    }

    $form->end();

    // On n'affiche pas le bouton de création d'OF si on est sur la liste OF depuis l'onglet "OF" de la fiche commande
    if($fk_commande>0)
    {
        $commande=new Commande($db);
        $commande->fetch($fk_commande);

        $r2 = new TSSRenderControler($assetOf);

        $sql = "SELECT c.rowid as fk_commandedet, p.rowid as rowid, p.ref as refProd, p.label as nomProd, c.qty as qteCommandee, c.description, c.label as lineLabel, c.product_type";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet c LEFT JOIN ".MAIN_DB_PREFIX."product p";
        $sql.= " ON (c.fk_product = p.rowid)";
        $sql.= " WHERE c.product_type IN (0,9) AND  c.fk_commande = ".$fk_commande;
        $sql.= " ORDER BY c.rang";

        $resql = $db->query($sql);
//var_dump($db);
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
            //print "<tr ".$bc[$var].">";

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
            $sql="SELECT ofe.rowid, ofe.numero, ofe.fk_soc, s.nom as client, SUM(IF(ofel.qty>0,ofel.qty,ofel.qty_needed) ) as nb_product_needed, ofel.fk_product, p.label as product, ofe.ordre, ofe.date_lancement , ofe.date_besoin
            , ofe.status, ofe.fk_user, ofe.total_cost ";

            // Add fields from hooks
            $parameters=array('listname' => 'OFListProductNeeded');
            $reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
            $sql.=$hookmanager->resPrint;

            $sql.=" FROM ".MAIN_DB_PREFIX."assetOf as ofe
              LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'NEEDED')
              LEFT JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = ofel.fk_product)
              LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = ofe.fk_soc)
              WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$fk_product." AND ofe.status!='CLOSE'";

            // Add where from hooks
            $parameters=array('listname' => 'OFListProductNeeded');
            $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
            $sql.=$hookmanager->resPrint;


            $sql.=" GROUP BY ofe.rowid ";

            if($conf->global->ASSET_OF_LIST_BY_ROWID_DESC) $orderBy['ofe.rowid']='DESC';
            else $orderBy['ofe.date_cre']='DESC';

            $TMath=array();
            $THide = array('rowid','fk_user','fk_product','fk_soc');
            if(empty($user->rights->{ ATM_ASSET_NAME }->of->price)) $THide[] = 'total_cost';
            else $TMath['total_cost']='sum';

            $TMath['nb_product_needed']='sum';

            $l=new TListviewTBS('listeofproductneeded');
            echo $langs->trans('ofListProductNeeded');
            $listViewConfig =array(
                'limit'=>array(
                    'nbLine'=>$conf->liste_limit
                )
            ,'orderBy'=>$orderBy
            ,'subQuery'=>array()
            ,'link'=>array(
                    'Utilisateur en charge'=>'<a href="'.dol_buildpath('/user/card.php?id=@fk_user@', 1).'">'.img_picto('','object_user.png','',0).' @val@</a>'
                ,'numero'=>'<a href="'.dol_buildpath('/of/fiche_of.php?id=@rowid@', 1).'">'.img_picto('','object_list.png','',0).' @val@</a>'
                )
            ,'translate'=>array()
            ,'hide'=>$THide
            ,'type'=>array(
                    'date_lancement'=>'date'
                ,'date_besoin'=>'date'
                ,'total_cost'=>'money'
                ,'nb_product_needed'=>'number'
                )
            ,'math'=>$TMath
            ,'liste'=>array(
                    'titre'=>$langs->trans('ListOFAsset')
                ,'image'=>img_picto('','title.png', '', 0)
                ,'picto_precedent'=>img_picto('','back.png', '', 0)
                ,'picto_suivant'=>img_picto('','next.png', '', 0)
                ,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
                ,'messageNothing'=>$langs->trans('noOfFound')
                ,'picto_search'=>img_picto('','search.png', '', 0)
                )
            ,'title'=>array(
                    'numero'=>$langs->trans('OfNumber')
                ,'ordre'=>$langs->trans('Priority')
                ,'date_lancement'=>$langs->trans('DateStart')
                ,'date_besoin'=>$langs->trans('DateNeeded')
                ,'status'=>$langs->trans('Statut')
                ,'login'=>$langs->trans('UserAssign')
                ,'product'=>$langs->trans('Product')
                ,'client'=>$langs->trans('Customer')
                ,'nb_product_needed'=>$langs->trans('NbProductNeeded')
                ,'total_cost'=>$langs->trans('Cost')
                )
            ,'eval'=>array(
                    'ordre'=>'TAssetOF::ordre("@val@")'
                ,'status'=>'TAssetOF::status("@val@")'
                ,'product' => 'get_format_libelle_produit(@fk_product@)'
                ,'client' => 'get_format_libelle_societe(@fk_soc@)'
                )
            );

            // Change view from hooks
            $parameters=array(
                'listViewConfig' => $listViewConfig,
                'listname' => 'OFListProductNeeded'
            );
            $reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            if ($reshook>0)
            {
                $listViewConfig = $hookmanager->resArray;
            }

            echo $l->render($PDOdb, $sql, $listViewConfig);
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

    if($_REQUEST['TListTBS']['list_llx_assetOf']['search']['status'] == 'OPEN') {

        dol_include_once('/of/class/of_amount.class.php');

        $aa=new AssetOFAmounts($db);

        $sql = "SELECT date, amount_estimated,amount_real FROM ".MAIN_DB_PREFIX.$aa->table_element;
        $l=new Listview($db,'listOFAmountsHistory');

        echo $l->render($sql, array(
            'list'=>array(
                'title'=>$langs->trans('listOFAmountsHistory')

            )
        ,'sortfield'=>'date'
        ,'sortorder'=>'DESC'
        ,'type'=>array(
                'date'=>'date'
            ,'amount_estimated'=>'number'
            ,'amount_real'=>'number'
            )
        ,'title'=>array(
                'date'=>$langs->trans('Date')
            ,'amount_real'=>$langs->trans('RealCost')
            ,'amount_estimated'=>$langs->trans('EstimatedCost')
            )
        ));
    }

    dol_fiche_end(-1);

    $PDOdb->close();
    llxFooter('');
}