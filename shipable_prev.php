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

$TLines = array();
$PDOdb = new TPDOdb;

$TProductId = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste
$TProductStock = array(); //On récupère tous les products id pour les associés à leur stock physique actuel car on va les décrementer au fur et à mesure que nous créons la liste

/*
 * On récupère toutes les lignes de commandes non livrées, ni annulées, et s'il y en a un, l'of lié
 */
$sql = "SELECT DISTINCT cd.rowid, aol.fk_assetOf, cde.svpm_date_livraison FROM ".MAIN_DB_PREFIX."commandedet as cd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (cd.fk_commande = c.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet_extrafields as cde ON (cde.fk_object = cd.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line as aol ON (aol.fk_commandedet = cd.rowid)";
$sql .= " WHERE c.fk_statut NOT IN (".Commande::STATUS_CANCELED.",".Commande::STATUS_CLOSED.")";
$sql .= " ORDER BY cde.svpm_date_livraison";

$resql = $db->query($sql);

if(!empty($resql) && $db->num_rows($resql)>0){
    while($obj = $db->fetch_object($resql)){
        $orderLine = new OrderLine($db);
        $nomenclature = new TNomenclature;

        $orderLine->fetch($obj->rowid);
        $orderLine->fk_assetOf = $obj->fk_assetOf;//On récup l'asset
        $nomenclature->loadByObjectId($PDOdb,$obj->rowid, 'commande', false,  0, $orderLine->qty, 0);
        if(empty($nomenclature->rowid))$nomenclature->loadByObjectId($PDOdb,$orderLine->fk_product,'product',false,  0, $orderLine->qty, 0);
        if(!empty($nomenclature->rowid)) {
            $orderLine->nomenclature = $nomenclature; //On récup la nomenclature
            if(!empty($nomenclature->TNomenclatureDet)){
                foreach($nomenclature->TNomenclatureDet as $nomenclaturedet){ //On init aussi le tableau avec les produits liés à la nomenclature
                    if(!empty($nomenclaturedet->fk_product))$TProductId[$nomenclaturedet->fk_product] = $nomenclaturedet->fk_product;
                }
            }
        }
        if(!empty($orderLine->fk_product))$TProductId[$orderLine->fk_product] = $orderLine->fk_product; // on init le produit avec tous les produits
        $TLines[] = $orderLine;
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
        $TProductStock[$obj->fk_product]['supplier_order'][$obj->svpm_date_livraison] += $obj->qty;
    }
}
var_dump($TProductStock);exit;

/*
 * VIEW
 */
llxHeader();



llxFooter();