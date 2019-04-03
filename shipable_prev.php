<?php
/**
 * Created by PhpStorm.
 * User: quentin
 * Date: 28/03/19
 * Time: 14:48
 */
require('config.php');
//TODO tester l'état actuel + ajout colonne soc
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/of/class/ordre_fabrication_asset.class.php');
dol_include_once('/nomenclature/class/nomenclature.class.php');
dol_include_once('/fourn/class/fournisseur.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/of/lib/of.lib.php');

if(empty($conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD) || empty($conf->global->OF_DELIVERABILITY_REPORT_SUPPLIERORDER_DATE_EXTRAFIELD)){
    accessforbidden($langs->trans('FillReportConf'));
}

$search_cmd=GETPOST('search_cmd','alpha');
$search_prod=GETPOST('search_prod','alpha');
$search_of=GETPOST('search_of','alpha');
$search_delivery_start=GETPOST("search_delivery_start");
$search_delivery_end=GETPOST("search_delivery_end");
$search_delivery_startday=GETPOST("search_delivery_startday","int");
$search_delivery_startmonth=GETPOST("search_delivery_startmonth","int");
$search_delivery_startyear=GETPOST("search_delivery_startyear","int");
$search_delivery_endday=GETPOST("search_delivery_endday","int");
$search_delivery_endmonth=GETPOST("search_delivery_endmonth","int");
$search_delivery_endyear=GETPOST("search_delivery_endyear","int");
$search_qty=GETPOST("search_qty");
$viewstatut=GETPOST('viewstatut');




$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');

if(empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if(!$sortfield) $sortfield = 'cde.'.$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD;
if(!$sortorder) $sortorder = 'DESC';


// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_delivery_start=null;
    $search_delivery_end=null;
    $search_cmd='';
    $search_prod='';
    $search_of='';
    $search_delivery_startday='';
    $search_delivery_startmonth='';
    $search_delivery_startyear='';
    $search_delivery_endday='';
    $search_delivery_endmonth='';
    $search_delivery_endyear='';
    $search_qty='';
    $viewstatut='';
}

$TLines = array();
$TLinesToDisplay = array();
$PDOdb = new TPDOdb;
$form = new Form($db);
$TProductId = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste
$TProductStock = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste

$langs->load('orders');
$langs->load('deliveries');
/*
 * On récupère toutes les lignes de commandes non livrées, ni annulées, et s'il y en a un, l'of lié pour pouvoir faire le traitement (lignes non filtrées)
 */
$sql = "SELECT DISTINCT cd.rowid, aol.fk_assetOf, cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD.", SUM(ed.qty) as qty_exped FROM " . MAIN_DB_PREFIX . "commandedet as cd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande as c ON (cd.fk_commande = c.rowid)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet_extrafields as cde ON (cde.fk_object = cd.rowid)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "assetOf_line as aol ON (aol.fk_commandedet = cd.rowid)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "assetOf as ao ON (aol.fk_assetOf = ao.rowid)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee ON (ee.fk_source = c.rowid AND ee.sourcetype='commande' AND ee.targettype='shipping')";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expedition as e ON (e.rowid = ee.fk_target)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON (p.rowid = cd.fk_product)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expeditiondet as ed ON (ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid)";
$sqlWhere .= " WHERE c.fk_statut NOT IN (" . Commande::STATUS_CANCELED . "," . Commande::STATUS_CLOSED . ") AND p.fk_product_type=0 AND p.rowid IS NOT NULL";

$sqlGroup .= " GROUP BY cd.rowid, aol.fk_assetOf, cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD;
/*
 * TRAITEMENT GLOBAL
 */
$result = $db->query($sql.$sqlWhere.$sqlGroup . " ORDER BY cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD.", cd.rowid");
if(!empty($result) && $db->num_rows($result) > 0) {
    while($obj = $db->fetch_object($result)) {
        $orderLine = new OrderLine($db);
        $nomenclature = new TNomenclature;

        $orderLine->fetch($obj->rowid);
        $orderLine->fetch_optionals();
        $orderLine->fk_assetOf = $obj->fk_assetOf;//On récup l'asset
        $nomenclature->loadByObjectId($PDOdb, $obj->rowid, 'commande', false, 0, $orderLine->qty, 0);
        if(empty($nomenclature->rowid)) $nomenclature->loadByObjectId($PDOdb, $orderLine->fk_product, 'product', false, 0, $orderLine->qty, 0);
        if(!empty($nomenclature->rowid)) {
            $details_nomenclature = $nomenclature->getDetails(1);

            $orderLine->nomenclature = $nomenclature; //On récup la nomenclature
            $orderLine->details_nomenclature = $details_nomenclature; //On récup la nomenclature

            //On init aussi le tableau avec les produits liés à la nomenclature (récursivement =) )
            if(!empty($details_nomenclature)) _getProductIdFromNomen($TProductId, $details_nomenclature);
        }
        if(!empty($orderLine->fk_product)) $TProductId[$orderLine->fk_product] = $orderLine->fk_product; // on init le produit avec tous les produits
        $orderLine->qty_exped = $obj->qty_exped;
        $TLines[$orderLine->id] = $orderLine;
    }
}

//Une fois que le traitement est fait on peut filtrer
if ($search_cmd) $sqlWhere .= natural_search('c.ref', $search_cmd);
if ($search_prod) $sqlWhere .= natural_search('p.ref', $search_prod);
if ($search_of) $sqlWhere .= natural_search('ao.numero', $search_of);
if ($search_qty != '') $sqlWhere.= natural_search("cd.qty", $search_qty, 1);
if ($viewstatut <> '')
{
    if ($viewstatut < 4 && $viewstatut > -3)
    {
        if ($viewstatut == 1 && empty($conf->expedition->enabled)) $sqlWhere.= ' AND c.fk_statut IN (1,2)';	// If module expedition disabled, we include order with status 'sending in process' into 'validated'
        else $sqlWhere.= ' AND c.fk_statut = '.$viewstatut; // brouillon, validee, en cours, annulee
    }
    if ($viewstatut == 4)
    {
        $sqlWhere.= ' AND c.facture = 1'; // invoice created
    }
    if ($viewstatut == -2)	// To process
    {
        //$sqlWhere.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
        $sqlWhere.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";    // If status is 2 and facture=1, it must be selected
    }
    if ($viewstatut == -3)	// To bill
    {
        //$sqlWhere.= ' AND c.fk_statut in (1,2,3)';
        //$sqlWhere.= ' AND c.facture = 0'; // invoice not created
        $sqlWhere .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'; // validated, in process or closed but not billed
    }
}
if(!empty($search_delivery_startday) && !empty($search_delivery_endday)){
    $sqlWhere.= " AND  (cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD." >= '".$search_delivery_startyear."-".$search_delivery_startmonth."-".$search_delivery_startday."'
                   AND cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD." <= '".$search_delivery_endyear."-".$search_delivery_endmonth."-".$search_delivery_endday."')";
} else if(!empty($search_delivery_startday)  && empty($search_delivery_endday) ){

    $sqlWhere.= " AND  (cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD." >= '".$search_delivery_startyear."-".$search_delivery_startmonth."-".$search_delivery_startday."')";
} else if(empty($search_delivery_startday)  && !empty($search_delivery_endday) ){
    $sqlWhere.= "  AND (cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD." <= '".$search_delivery_endyear."-".$search_delivery_endmonth."-".$search_delivery_endday."')";
}

$sql .= $sqlWhere.$sqlGroup; // Obliger de faire le travail 2x (1 pour avoir toutes les données et faire le traitement, et l'autre pour le filtrage et l'affichage car le traitement se fait ligne à ligne (qté décrémenté ligne par ligne))

$result = $db->query($sql . " ORDER BY cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD.", cd.rowid");
$nbtotalofrecords = $db->num_rows($result);

if(($page * $limit) > $nbtotalofrecords)    // if total resultset is smaller then paging size (filtering), goto and load page 0
{
    $page = 0;
    $offset = 0;
}

$sql .= $db->order($sortfield . ',cd.rowid', $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
$num = $db->num_rows($resql);
if(!empty($resql) && $db->num_rows($resql) > 0) {
    while($obj = $db->fetch_object($resql)) {
        $orderLine = new OrderLine($db);
        $nomenclature = new TNomenclature;

        $TLinesToDisplay[$obj->rowid] = $obj->rowid;
    }
}
/*
 * A présent on récupère le stock physique de chaque produit ainsi que le stock contenu dans chaque commande fourn
 */
$sql = "SELECT p.stock, p.rowid FROM " . MAIN_DB_PREFIX . "product as p WHERE rowid IN (" . implode(',', $TProductId) . ")";
$resql = $db->query($sql);

if(!empty($resql) && $db->num_rows($resql) > 0) {
    while($obj = $db->fetch_object($resql)) {
        $TProductStock[$obj->rowid] = array('stock' => $obj->stock);
    }
}

$sql = " SELECT cfd.qty, cfde.".$conf->global->OF_DELIVERABILITY_REPORT_SUPPLIERORDER_DATE_EXTRAFIELD.", cfd.fk_product, cf.rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cfd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet_extrafields as cfde ON (cfde.fk_object = cfd.rowid)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cf ON (cf.rowid = cfd.fk_commande)";
$sql .= " WHERE cfd.fk_product IN (" . implode(',', $TProductId) . ") ";
$sql .= " AND cf.fk_statut NOT IN (" . CommandeFournisseur::STATUS_RECEIVED_COMPLETELY . ", " . CommandeFournisseur::STATUS_CANCELED . ", " . CommandeFournisseur::STATUS_CANCELED_AFTER_ORDER . ", " . CommandeFournisseur::STATUS_REFUSED . ")";
$sql .= " ORDER BY cfde.".$conf->global->OF_DELIVERABILITY_REPORT_SUPPLIERORDER_DATE_EXTRAFIELD;

$resql = $db->query($sql);
if(!empty($resql) && $db->num_rows($resql) > 0) {
    while($obj = $db->fetch_object($resql)) {
//        $TProductStock[$obj->fk_product]['supplier_order']['total_from_supplier'] += $obj->qty;
        $TProductStock[$obj->fk_product]['supplier_order'][$obj->{$conf->global->OF_DELIVERABILITY_REPORT_SUPPLIERORDER_DATE_EXTRAFIELD}][$obj->rowid] += $obj->qty;
    }
}
//Recursively check if stock is enough
$TDetailStock = array();

foreach($TLines as $key => $line) {
    _getDetailStock($line, $TProductStock, $TDetailStock); //On met dans TDetailStock le détail du stock si on a assez ou pas
}

/*
 * VIEW
 */
llxHeader();

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($search_cmd )             $param.='&search_cmd='.urlencode($search_cmd);
if ($search_prod )             $param.='&search_prod='.urlencode($search_prod);
if ($search_of )             $param.='&search_of='.urlencode($search_of);
if ($search_qty != '')             $param.='&search_qty='.urlencode($search_qty);
if ($viewstatut != '')      $param.='&viewstatut='.urlencode($viewstatut);
if ($search_delivery_startday)   		$param.='&search_delivery_startday='.urlencode($search_delivery_startday);
if ($search_delivery_startmonth)   		$param.='&search_delivery_startmonth='.urlencode($search_delivery_startmonth);
if ($search_delivery_startyear)    		$param.='&search_delivery_startyear='.urlencode($search_delivery_startyear);
if ($search_delivery_endday)   		$param.='&search_delivery_endday='.urlencode($search_delivery_endday);
if ($search_delivery_endmonth)   		$param.='&search_delivery_endmonth='.urlencode($search_delivery_endmonth);
if ($search_delivery_endyear)    		$param.='&search_delivery_endyear='.urlencode($search_delivery_endyear);
if ($search_delivery_end)    		$param.='&search_delivery_end='.urlencode($search_delivery_end);
if ($search_delivery_start)    		$param.='&search_delivery_start='.urlencode($search_delivery_start);


print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="viewstatut" value="' . $viewstatut . '">';
print '<input type="hidden" name="socid" value="' . $socid . '">';

print_barre_liste($langs->trans('ShippablePrevReport'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, $newcardbutton, '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

print '<tr class="liste_titre_filter">';

print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_cmd" value="' . $search_cmd . '">';
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_prod" value="' . $search_prod . '">';
print '</td>';
print '<td class="liste_titre">';
if(!empty($search_delivery_end)) {
    $search_delivery_end = str_replace('/', '-', $search_delivery_end);
    $search_delivery_end = strtotime($search_delivery_end);

} else $search_delivery_end=null;
if(!empty($search_delivery_start)) {
    $search_delivery_start = str_replace('/', '-', $search_delivery_start);
    $search_delivery_start = strtotime($search_delivery_start);
} else $search_delivery_start=null;
print $form->selectDate($search_delivery_start, 'search_delivery_start');
print $form->selectDate($search_delivery_end, 'search_delivery_end');
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_of" value="' . $search_of . '">';
print '</td>';
print '<td class="liste_titre maxwidthonsmartphone" align="right">';
$liststatus = array(
    Commande::STATUS_DRAFT => $langs->trans("StatusOrderDraftShort"),
    Commande::STATUS_VALIDATED => $langs->trans("StatusOrderValidated"),
    Commande::STATUS_SHIPMENTONPROCESS => $langs->trans("StatusOrderSentShort"),
    Commande::STATUS_CLOSED => $langs->trans("StatusOrderDelivered"),
    -3 => $langs->trans("StatusOrderValidatedShort") . '+' . $langs->trans("StatusOrderSentShort") . '+' . $langs->trans("StatusOrderDelivered"),
    Commande::STATUS_CANCELED => $langs->trans("StatusOrderCanceledShort")
);
print $form->selectarray('viewstatut', $liststatus, $viewstatut, -4, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
print '</td>';

print '<td class="liste_titre" align="right">';
print '<input class="flat" type="text" size="5" name="search_qty" value="' . dol_escape_htmltag($search_qty) . '">';
print '</td>';

// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

// Fields title
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Order'), $_SERVER["PHP_SELF"], 'c.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Product'), $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('DeliveryDate'), $_SERVER["PHP_SELF"], "cde.".$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD, "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('OFAsset'), $_SERVER["PHP_SELF"], 'ao.numero', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Status'), $_SERVER["PHP_SELF"], 'c.fk_statut', '', $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Quantity'), $_SERVER["PHP_SELF"], 'cd.qty', '', $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('');
print '</tr>' . "\n";
/*
 * Lines
 */
foreach($TLinesToDisplay as $lineid) {
    if(!empty($TLines[$lineid])) {
        $commande = new Commande($db);
        $commande->fetch($TLines[$lineid]->fk_commande);
        $TLines[$lineid]->fetch_product();
        if(!empty($TDetailStock[$lineid]['status'])) $color = '#8DDE8D';
        else if(empty($TLines[$lineid]->array_options['options_'.$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD])) $color = '#dedb8d';
        else $color = '#de8d8d';
        if(!empty($TLines[$lineid]->fk_assetOf)) {
            $assetOF = new TAssetOF;
            $assetOF->load($PDOdb, $TLines[$lineid]->fk_assetOf);
            $print_of = $assetOF->getNomUrl(1);
        }
        else {
            $print_of = '';
        }

        $stock_tooltip = '';
        if(!empty($TDetailStock[$lineid])) {
            _getPictoDetail($TDetailStock, $lineid, $stock_tooltip);
        }
    }

    print '<tr class="oddeven" style="background: ' . $color . ';">';

    print '<td>';
    print $commande->getNomUrl(1);
    print '</td>';
    print '<td>';
    print $TLines[$lineid]->product->getNomUrl(1);
    print '</td>';
    print '<td>';
    if(!empty($TLines[$lineid]->array_options['options_'.$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD])) print date('d/m/Y', $TLines[$lineid]->array_options['options_'.$conf->global->OF_DELIVERABILITY_REPORT_ORDER_DATE_EXTRAFIELD]);
    else print  $langs->trans('WarningNoDate') . ' ' . img_picto($langs->trans('pictoNoDate'), 'warning');
    print '</td>';
    print '<td>';
    print $print_of;
    print '</td>';
    print '<td align="right">';
    print $commande->getLibStatut(2);
    print '</td>';
    print '<td align="right">';

    print $form->textwithpicto($TLines[$lineid]->qty, $stock_tooltip);
    print '</td>';
    print '<td></td>';
    print '</tr>';
}

llxFooter();