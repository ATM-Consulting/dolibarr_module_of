<?
/* A la facturation on créé les équipements concerné 
 * object contient la facture
 * 0. S'il y a déjà des équipements, on ne fait pas
 * 1. lecture des lignes de facture et récupération des produits
 * 2. création des équipements sur les lignes de facture
 * */

 define('INC_FROM_CRON_SCRIPT', true);
 require('config.php');	
	
 require('./class/asset.class.php');
 
 _create($_REQUEST['facid'],'facture',$_REQUEST['entity']);
 
 
function _create($fk_document, $type_document,$entity) {
	$db=new Tdb;
	
	/*
	 * S'il y a déjà des équipements liés à la facture, on ne les créés pas 
	 */
	$db->Execute("SELECT count(*) as 'nb' FROM '.MAIN_DB_PREFIX.ATM_ASSET_NAME.'_link WHERE fk_document=".$fk_document." AND type_document='".$type_document."'");
	$db->Get_line();
	if((int)$db->Get_field('nb')>0) return false;
	
	
	/*
	 * finished = 1 -> produit manufacturé
	 * fk_product_type = 0 -> produit (à la différence de service en 1)
	 */
	
	$db->Execute("SELECT l.qty as 'qty', p.rowid as 'fk_product',l.price as 'price', f.fk_soc as 'fk_soc'
	FROM (('.MAIN_DB_PREFIX.'facturedet l LEFT JOIN '.MAIN_DB_PREFIX.'facture f ON (l.fk_facture=f.rowid))
				LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (l.fk_product=p.rowid))
	
	WHERE f.rowid=".$fk_document." AND p.fk_product_type=0 AND p.finished=1
	");
	$Tab=array();
	while($db->Get_line()) {
		$Tab[]=array(
			'qty'=>$db->Get_field('qty')
			,'fk_product'=>$db->Get_field('fk_product')
			,'price'=>$db->Get_field('price')
			,'fk_soc'=>$db->Get_field('fk_soc')
		);		
	}
	
	foreach ($Tab as $row) {
			if(isset($_REQUEST['DEBUG'])) {
				print_r($row);				
			}	
		
		for($i=0;$i<$row['qty'];$i++) {
			$asset=new TAsset;
			$asset->fk_soc = $row['fk_soc'];
			$asset->fk_product = $row['fk_product'];
			//$asset->prix_achat = $row['price'];
			$asset->entity=$entity;
			
			$asset->add_link($fk_document,$type_document);
			
			$asset->save($db);
			
		}
		
	}
	
	
	$db->close();
	
	return true;	
} 
?>