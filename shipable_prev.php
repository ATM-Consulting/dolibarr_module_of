<?php
/**
 * Created by PhpStorm.
 * User: quentin
 * Date: 28/03/19
 * Time: 14:48
 */
require('config.php');

dol_include_once('/commande/class/commande.class.php');
dol_include_once('/of/class/ordre_fabrication_asset.class.php');
dol_include_once('/nomenclature/class/nomenclature.class.php');
dol_include_once('/fourn/class/fournisseur.class.php');
dol_include_once('/core/class/html.form.class.php');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='cde.svpm_date_livraison';
if (! $sortorder) $sortorder='DESC';



$TLines = array();
$TLinesToDisplay = array();
$PDOdb = new TPDOdb;
$form = new Form($db);
$TProductId = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste
$TProductStock = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste

$langs->load('orders');
$langs->load('deliveries');
/*
 * On récupère toutes les lignes de commandes non livrées, ni annulées, et s'il y en a un, l'of lié
 */
$sql = "SELECT DISTINCT cd.rowid, aol.fk_assetOf, cde.svpm_date_livraison, SUM(ed.qty) as qty_exped FROM ".MAIN_DB_PREFIX."commandedet as cd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (cd.fk_commande = c.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields as cde ON (cde.fk_object = cd.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line as aol ON (aol.fk_commandedet = cd.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON (ee.fk_source = c.rowid AND ee.sourcetype='commande' AND ee.targettype='shipping')";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as e ON (e.rowid = ee.fk_target)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid)";
$sql .= " WHERE c.fk_statut NOT IN (".Commande::STATUS_CANCELED.",".Commande::STATUS_CLOSED.")";
$sql .= " GROUP BY cd.rowid, aol.fk_assetOf, cde.svpm_date_livraison";


if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql." ORDER BY cde.svpm_date_livraison");
    $nbtotalofrecords = $db->num_rows($result);
    if(!empty($result) && $db->num_rows($result)>0){
        while($obj = $db->fetch_object($result)){
            $orderLine = new OrderLine($db);
            $nomenclature = new TNomenclature;

            $orderLine->fetch($obj->rowid);
            $orderLine->fk_assetOf = $obj->fk_assetOf;//On récup l'asset
            $nomenclature->loadByObjectId($PDOdb,$obj->rowid, 'commande', false,  0, $orderLine->qty, 0);
            if(empty($nomenclature->rowid))$nomenclature->loadByObjectId($PDOdb,$orderLine->fk_product,'product',false,  0, $orderLine->qty, 0);
            if(!empty($nomenclature->rowid)) {
                $details_nomenclature = $nomenclature->getDetails($orderLine->qty);

                $orderLine->nomenclature = $nomenclature; //On récup la nomenclature
                $orderLine->details_nomenclature = $details_nomenclature; //On récup la nomenclature
                if(!empty($nomenclature->TNomenclatureDet)){
                    //On init aussi le tableau avec les produits liés à la nomenclature (récursivement =) )

                    _getProductIdFromNomen($TProductStock);

                }
            }
            if(!empty($orderLine->fk_product))$TProductId[$orderLine->fk_product] = $orderLine->fk_product; // on init le produit avec tous les produits
            $orderLine->qty_exped = $obj->qty_exped;
            $TLines[$orderLine->id] = $orderLine;
        }
    }
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

$resql = $db->query($sql);
$num = $db->num_rows($resql);
if(!empty($resql) && $db->num_rows($resql)>0){
    while($obj = $db->fetch_object($resql)){
        $orderLine = new OrderLine($db);
        $nomenclature = new TNomenclature;

        $TLinesToDisplay[$obj->rowid]=$obj->rowid;
    }
}
/*
 * A présent on récupère le stock physique de chaque produit ainsi que le stock contenu dans chaque commande fourn
 */
$sql = "SELECT p.stock, p.rowid FROM ".MAIN_DB_PREFIX."product as p WHERE rowid IN (".implode(',',$TProductId).")";
$resql = $db->query($sql);

if(!empty($resql) && $db->num_rows($resql)>0) {
    while($obj = $db->fetch_object($resql)) {
        $TProductStock[$obj->rowid]=array('stock'=>$obj->stock);
    }
}

$sql = " SELECT cfd.qty, cfde.svpm_date_livraison, cfd.fk_product FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cfd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet_extrafields as cfde ON (cfde.fk_object = cfd.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cf ON (cf.rowid = cfd.fk_commande)";
$sql .= " WHERE cfd.fk_product IN (".implode(',',$TProductId).") ";
$sql .= " AND cf.fk_statut NOT IN (".CommandeFournisseur::STATUS_RECEIVED_COMPLETELY.", ".CommandeFournisseur::STATUS_CANCELED.", ".CommandeFournisseur::STATUS_CANCELED_AFTER_ORDER.", ".CommandeFournisseur::STATUS_REFUSED.")";
$sql .= " ORDER BY cfde.svpm_date_livraison";

$resql = $db->query($sql);
if(!empty($resql) && $db->num_rows($resql)>0) {
    while($obj = $db->fetch_object($resql)) {
        $TProductStock[$obj->fk_product]['supplier_order']['total_from_supplier'] += $obj->qty;
        $TProductStock[$obj->fk_product]['supplier_order'][$obj->svpm_date_livraison] += $obj->qty;
    }
}

//Recursively check if stock is enough
$TDetailStock = array();
foreach ($TLines as $key => $line) $TDetailStock = _getDetailStock($line, $TProductStock);


/*
 * VIEW
 */
llxHeader();



print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';


print_barre_liste($langs->trans('ShippablePrevReport'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, $newcardbutton, '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre_filter">';

print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_cmd" value="'.$search_cmd.'">';
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_prod" value="'.$search_prod.'">';
print '</td>';
print '<td class="liste_titre">';
print $form->selectDate($search_delivery_start,'search_delivery_start');
print $form->selectDate($search_delivery_end,'search_delivery_end');
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_of" value="'.$search_of.'">';
print '</td>';
print '<td class="liste_titre maxwidthonsmartphone" align="right">';
$liststatus=array(
    Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"),
    Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
    Commande::STATUS_SHIPMENTONPROCESS=>$langs->trans("StatusOrderSentShort"),
    Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
    -3=>$langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort").'+'.$langs->trans("StatusOrderDelivered"),
    Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
);
print $form->selectarray('viewstatut', $liststatus, $viewstatut, -4, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
print '</td>';

print '<td class="liste_titre" align="right">';
print '<input class="flat" type="text" size="5" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
print '</td>';

// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

// Fields title
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Order'),$_SERVER["PHP_SELF"],'c.ref','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Product'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('DeliveryDate'),$_SERVER["PHP_SELF"],"cde.svpm_date_livraison","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('OFAsset'),$_SERVER["PHP_SELF"],'ao.numero','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'c.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Quantity'),$_SERVER["PHP_SELF"],'cd.qty','',$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>'."\n";




llxFooter();