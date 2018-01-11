<?php
require '../../config.php';
set_time_limit ( 0 );

$db->query ( "SET sql_mode=''");
$res = $db->query ( "SELECT p.rowid,l.rowid as idLine, l.fk_assetof
    		FROM " . MAIN_DB_PREFIX . "product p 
    			INNER JOIN " . MAIN_DB_PREFIX . "assetOf_line l ON (l.fk_product=p.rowid)
					INNER JOIN " . MAIN_DB_PREFIX . "assetOf of ON (l.fk_assetof=of.rowid)
    		WHERE l.type='TO_MAKE' AND of.status='CLOSE'
    		GROUP BY p.rowid
    	ORDER BY l.fk_assetof ASC " );
if ($res === false) {
	var_dump ( $db );
	exit ();
}

dol_include_once ( '/of/class/ordre_fabrication_asset.class.php' );
$PDOdb = new TPDOdb ();

$TPMP = array ();
while ( $obj = $db->fetch_object ( $res ) ) {
	
	if (empty ( $obj->fk_assetof ))
		continue;
	
	$of = new TAssetOf ();
	$of->load ( $PDOdb, $obj->fk_assetof );
	
	echo $obj->fk_assetof . ' [' . $of->numero . '] ';
	if (_check_child_of ( $PDOdb, $of )) {
		$of->load ( $PDOdb, $obj->fk_assetof );
	}
	
	$of->set_current_cost_for_to_make ();
	
	$pmp = ( float ) price2num ( $of->current_cost_for_to_make, 2 );
	if ($pmp <= 0){echo '<br />';continue; }
	
	foreach ($of->TAssetOFLine as &$line) {
		if($line->type === 'TO_MAKE' && $line->qty_used>0 && $line->fk_product == $obj->rowid) {
			
			@$TPMP[$obj->rowid][] = array (
					$line->qty_used,
					$pmp
					
			);
			
			echo $line->fk_product. ' => ' . $pmp . ' x '.$line->qty_used.' )) ';
		}
	}
	
	echo '<br />';
	// var_dump($obj, $pmp);exit;
	
	flush ();
}

if(GETPOST('forReal')=='') {
	
	echo 'Pour continuer : ?forReal=1 ';
	
	pre($TPMP, 1);
	
	exit;
}

// pre($TPMP,1);exit;

foreach ( $TPMP as $fk_product => $data ) {
	
	$qty = 0;
	$price = 0;
	foreach ( $data as $pmp ) {
		$qty += $pmp [0];
		$price += $pmp [0]* $pmp [1];
	}
	
	$_pmp = price2num ( $price / $qty, 2 );
	
	//var_dump($data,$_pmp);exit;
	$db->query ( "UPDATE " . MAIN_DB_PREFIX . "product
                            SET pmp=" . $_pmp . "
                    WHERE rowid=" . $fk_product . "
                    " );
	
	echo 'R ' . $fk_product . ' => ' . $_pmp . '<br />';
	flush ();
}

echo 'END';
function _check_child_of(&$PDOdb, &$of) {
	$TId = array ();
	$of->getListeOFEnfants ( $PDOdb, $TId, null, false );
	
	$find = false;
	foreach ( $TId as $fk_of ) {
		$child = new TAssetOf ();
		$child->load ( $PDOdb, $fk_of );
		
		if (_check_child_of ( $PDOdb, $child )) {
			$child->load ( $PDOdb, $fk_of );
		}
		
		$child->set_current_cost_for_to_make ();
		
		$childline = $child->getLineProductToMake ();
		
		foreach ( $of->TAssetOFLine as &$line ) {
			if ($line->type == 'NEEDED' && $line->fk_product == $childline->fk_product) {
				echo '--- enfant produisant ' . $child->getId () . '=>' . $childline->fk_product . ' --- ';
				$line->setPMP ( $PDOdb, $child->current_cost_for_to_make );
				$find = true;
				// var_dump($child->current_cost_for_to_make, $child);exit;
			}
		}
	}
	
	return $find;
}

