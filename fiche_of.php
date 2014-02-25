<?php

require('config.php');

require('./class/asset.class.php');
require('./class/ordre_fabrication_asset.class.php');
require('./lib/asset.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


if(!$user->rights->asset->all->lire) accessforbidden();
if(!$user->rights->asset->of->write) accessforbidden();


// Load traductions files requiredby by page
$langs->load("other");

// Get parameters
_action();

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

function _action() {
	global $user;	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;
	
	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	$action=__get('action','view');
	
	
		switch($action) {
			case 'new':
			case 'add':
				$assetOf=new TAssetOF;
				$assetOf->set_values($_REQUEST);
				
				_fiche($PDOdb, $assetOf,'new');

				break;

			case 'edit'	:
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id']);

				_fiche($PDOdb,$assetOf,'edit');
				break;

			case 'save':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) {
					$assetOf->load($PDOdb, $_REQUEST['id'], false);
					$mode = 'view';
				}
				else {
					$mode = 'edit';
				}
				
				$assetOf->set_values($_REQUEST);
			
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						$assetOf->TAssetOFLine[$k]->set_values($row);
					}
			
					foreach($assetOf->TAssetOFLine as &$line) {
						$line->TAssetOFLine=array();
					}
				}
				
				
				if(!empty($_REQUEST['TAssetWorkstationOF'])) {
					foreach($_REQUEST['TAssetWorkstationOF'] as $k=>$row) {
						$assetOf->TAssetWorkstationOF[$k]->set_values($row);
					}
				}
				
				
				$assetOf->save($PDOdb);
				
				_fiche($PDOdb,$assetOf, $mode);

				break;
			
			case 'valider':
				
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "VALID";				
				
				
				if(!empty($_REQUEST['TAssetOFLine'])) {
					foreach($_REQUEST['TAssetOFLine'] as $k=>$row) {
						$assetOf->TAssetOFLine[$k]->set_values($row);
					}
				}
				$assetOf->createOfAndCommandesFourn($PDOdb);
				//$assetOf->openOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($PDOdb,$assetOf, 'view');

				break;
				
			case 'lancer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "OPEN";
				//$assetOf->openOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($PDOdb, $assetOf, 'view');

				break;
				
			case 'terminer':
				$assetOf=new TAssetOF;
				if(!empty($_REQUEST['id'])) $assetOf->load($PDOdb, $_REQUEST['id'], false);
				$assetOf->status = "CLOSE";
				$assetOf->closeOF($PDOdb);
				$assetOf->save($PDOdb);
				_fiche($PDOdb,$assetOf, 'view');
				break;
				
			case 'delete':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
				
				//$PDOdb->db->debug=true;
				$assetOf->delete($PDOdb);
				
				?>
				<script language="javascript">
					document.location.href="<?=dirname($_SERVER['PHP_SELF'])?>/liste_of.php?delete_ok=1";					
				</script>
				<?
				
				break;
				
			case 'view':
				$assetOf=new TAssetOF;
				$assetOf->load($PDOdb, $_REQUEST['id'], false);
		
				_fiche($PDOdb, $assetOf, 'view');
				
				break;
			case 'createDocOF':
				
				generateODTOF($PDOdb);
								
				break;				
		}
		
	
	
	
}



function generateODTOF(&$PDOdb) {
	
	global $db;

	$assetOf=new TAssetOF;
	$assetOf->load($PDOdb, $_REQUEST['id'], false);
	foreach($assetOf as $k => $v) {
		print $k."<br />";
	}
	//exit;
	
	/*echo "<pre>";
	print_r($assetOf->TAssetWorkstationOF);
	echo "</pre>";
	
	exit;*/
	$TBS=new TTemplateTBS();
	dol_include_once("/product/class/product.class.php");

	$TToMake = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit à fabriquer
	$TNeeded = array(); // Tableau envoyé à la fonction render contenant les informations concernant les produit nécessaires
	$TWorkstations = array(); // Tableau envoyé à la fonction render contenant les informations concernant les stations de travail

	// On charge les tableaux de produits à fabriquer, et celui des produits nécessaires
	foreach($assetOf->TAssetOFLine as $k=>$v) {
		
		if($v->type == "TO_MAKE") {
			
			$prod = new Product($db);
			$prod->fetch($v->fk_product);
			
			$TToMake[] = array(
							'type' => $v->type
							, 'qte' => $v->qty
							, 'nomProd' => $prod->ref
							, 'designation' => $prod->label
							, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
						);
			
		}
		if($v->type == "NEEDED") {
	
			$unitLabel = "";
			
			$prod = new Product($db);
			$prod->fetch($v->fk_product);						
			
			if($prod->weight_units == 0) {
				$unitLabel = "Kg";
			} else if ($prod->weight_units == -3) {
				$unitLabel = "g";
			} else if ($prod->weight_units == -6) {
				$unitLabel = "mg";
			} else if ($prod->weight_units == 99) {
				$unitLabel = "livre(s)";
			}
									
			$TNeeded[] = array(
							'type' => $v->type
							, 'qte' => $v->qty
							, 'nomProd' => $prod->ref
							, 'designation' => $prod->label
							, 'dateBesoin' => date("d/m/Y", $assetOf->date_besoin)
							, 'poids' => $prod->weight
							, 'unitPoids' => $unitLabel
							, 'finished' => $prod->finished?"PM":"MP"
						);
	
			
		}

	}

	/*echo "<pre>";
	print_r($TNeeded);
	echo "</pre>";
	exit;*/

	// On charge le tableau d'infos sur les stations de travail de l'OF courant
	foreach($assetOf->TAssetWorkstationOF as $k => $v) {
		
		$TWorkstations[] = array(
							'libelle' => $v->ws->libelle
							,'nb_hour_max' => $v->ws->nb_hour_max
							,'nb_heures_prevues' => $v->nb_hour
						);
		
	}
	
	$dirName = 'OF'.$_REQUEST['id'].'('.date("d_m_Y").')';
	$dir = DOL_DATA_ROOT.'/asset/'.$dirName.'/';
	
	@mkdir($dir, 0777, true);
	
	$TBS->render(dol_buildpath('/asset/exempleTemplate/templateOF.odt')
		,array(
			'lignesToMake'=>$TToMake
			,'lignesNeeded'=>$TNeeded
			,'lignesWorkstation'=>$TWorkstations
		)
		,array(
			'date'=>date("d/m/Y")
			,'numeroOF'=>$assetOf->numero
			,'statutOF'=>TAssetOF::$TStatus[$assetOf->status]
			,'prioriteOF'=>TAssetOF::$TOrdre[$assetOf->ordre]
			,'date'=>date("d/m/Y")
		)
		,array()
		,array(
			'outFile'=>$dir.'templateOF.odt'
		)
		
	);	
	
	header("Location: ".DOL_URL_ROOT."/document.php?modulepart=asset&entity=1&file=".$dirName."/templateOF.odt");

}

print '<a name="createFileOF" class="butAction" href="'.DOL_URL_ROOT.'/custom/asset/fiche_of.php?id='.$_REQUEST['id'].'&action=createDocOF">'.$langs->trans("createOFFile").'</a>';

?>
	<script>
		$("[name=createFileOF]").appendTo("div.OFContent div.tabsAction");
	</script>
<?


function _fiche_ligne(&$form, &$of, $type){
		global $db, $conf;
		
		$TRes = array();
		foreach($of->TAssetOFLine as $k=>$TAssetOFLine){
			$product = new Product($db);
			$product->fetch($TAssetOFLine->fk_product);
			
			if($TAssetOFLine->type == "NEEDED" && $type == "NEEDED"){
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'qty_needed'=>$TAssetOFLine->qty_needed
					,'qty'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,50) : $TAssetOFLine->qty
					,'qty_used'=>($of->status=='OPEN') ? $form->texte('', 'TAssetOFLine['.$k.'][qty_used]', $TAssetOFLine->qty_used, 5,50) : $TAssetOFLine->qty_used
					,'qty_toadd'=> $TAssetOFLine->qty - $TAssetOFLine->qty_used
					,'delete'=> '<a href="javascript:deleteLine('.$TAssetOFLine->getId().',\'NEEDED\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
			elseif($TAssetOFLine->type == "TO_MAKE" && $type == "TO_MAKE"){
			
				$Tab=array();
				foreach($TAssetOFLine->TFournisseurPrice as &$objPrice) {
						
					$Tab[ $objPrice->rowid ] = ($objPrice->price>0 ? floatval($objPrice->price).' '.$conf->currency : '') .' (Fournisseur "'.$objPrice->name.'", '.($objPrice->quantity >0 ? $objPrice->quantity.' pièce(s) min,' : '').' '.($objPrice->compose_fourni ? 'composé fourni' : 'composé non fourni' ).')';
					
				}	
				
				$TRes[]= array(
					'id'=>$TAssetOFLine->getId()
					,'idProd'=>$product->id
					,'libelle'=>'<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">'.img_picto('', 'object_product.png').$product->libelle.'</a>'
					,'addneeded'=> '<a href="#null" onclick="addAllLines('.$of->getId().','.$TAssetOFLine->getId().',this);">'.img_picto('Ajout des produit nécessaire', 'previous.png').'</a>'
					,'qty'=>($of->status=='DRAFT') ? $form->texte('', 'TAssetOFLine['.$k.'][qty]', $TAssetOFLine->qty, 5,5,'','') : $TAssetOFLine->qty 
					,'fk_product_fournisseur_price'=>($of->status=='DRAFT') ? $form->combo('', 'TAssetOFLine['.$k.'][fk_product_fournisseur_price]', $Tab, $TAssetOFLine->fk_product_fournisseur_price ) : $Tab[$TAssetOFLine->fk_product_fournisseur_price]
					,'delete'=> '<a href="#null" onclick="deleteLine('.$TAssetOFLine->getId().',\'TO_MAKE\');">'.img_picto('Supprimer', 'delete.png').'</a>'
				);
			}
		}
		
		return $TRes;
}



function _fiche(&$PDOdb, &$assetOf, $mode='edit') {
	global $langs,$db,$conf;
	/***************************************************
	* PAGE
	*
	* Put here all code to build page
	****************************************************/
	
	llxHeader('',$langs->trans('OFAsset'),'','');
	print dol_get_fiche_head(assetPrepareHead( $assetOf, 'assetOF') , 'fiche', $langs->trans('AssetOF'));
	

	?>	<div class="OFContent" rel="<?=$assetOf->getId() ?>">	<?php
	
	$TPrixFournisseurs = array();
	
	//$form=new TFormCore($_SERVER['PHP_SELF'],'formeq'.$assetOf->getId(),'POST');
	
	$form=new TFormCore();
	$form->Set_typeaff($mode);
	$doliform = new Form($db);
	
	if(!empty($_REQUEST['fk_product'])) echo $form->hidden('fk_product', $_REQUEST['fk_product']);
	
	$TBS=new TTemplateTBS();
	$liste=new TListviewTBS('asset');

	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	$PDOdb = new TPDOdb;
	
	$TNeeded = array();
	$TToMake = array();
	
	$TNeeded = _fiche_ligne($form, $assetOf, "NEEDED");
	$TToMake = _fiche_ligne($form, $assetOf, "TO_MAKE");
	
	ob_start();
	$html=new Form($db);
	$html->select_produits('','fk_product','',$conf->product->limit_size,0,1,2,'',3,array());
	$select_product = ob_get_clean();
	
	$Tid = array();
	//$Tid[] = $assetOf->rowid;
	if($assetOf->getId()>0) $assetOf->getListeOFEnfants($PDOdb, $Tid);
	
	
	$TWorkstation=array();
	foreach($assetOf->TAssetWorkstationOF as $k=>$TAssetWorkstationOF) {
		
		$ws = & $TAssetWorkstationOF->ws;
		
		$TWorkstation[]=array(
			'libelle'=>$ws->libelle
			,'nb_hour'=> ($assetOf->status=='DRAFT') ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour]', $TAssetWorkstationOF->nb_hour,3,10) : $TAssetWorkstationOF->nb_hour  
			,'nb_hour_real'=>($assetOf->status=='OPEN') ? $form->texte('','TAssetWorkstationOF['.$k.'][nb_hour_real]', $TAssetWorkstationOF->nb_hour_real,3,10) : $TAssetWorkstationOF->nb_hour_real
			,'delete'=> '<a href="javascript:deleteWS('.$assetOf->getId().','.$TAssetWorkstationOF->getId().');">'.img_picto('Supprimer', 'delete.png').'</a>'
			,'id'=>$ws->getId()
		);
		
	}
	
	
	$TOFParent = array_merge(array(0=>'')  ,$assetOf->getCanBeParent($PDOdb));
	
	print $TBS->render('tpl/fiche_of.tpl.php'
		,array(
			'TNeeded'=>$TNeeded
			,'TTomake'=>$TToMake
			,'workstation'=>$TWorkstation
		)
		,array(
			'assetOf'=>array(
				'id'=> $assetOf->getId()
				,'numero'=> ($mode=='edit') ? $form->texte('', 'numero', $assetOf->numero, 20,255,'','','à saisir') : '<a href="fiche_of.php?id='.$assetOf->getId().'">'.$assetOf->numero.'</a>'
				,'ordre'=>$form->combo('','ordre',TAssetOf::$TOrdre,$assetOf->ordre)
				,'fk_assetOf_parent'=>($mode=='edit') ? $form->combo('','fk_assetOf_parent',$TOFParent,$assetOf->fk_assetOf_parent) : '<a href="fiche_of.php?id='.$assetOf->fk_assetOf_parent.'">'.$TOFParent[$assetOf->fk_assetOf_parent].'</a>'
				,'date_besoin'=>$form->calendrier('','date_besoin',$assetOf->date_besoin,12,12)
				,'date_lancement'=>$form->calendrier('','date_lancement',$assetOf->date_lancement,12,12)
				,'temps_estime_fabrication'=>$assetOf->temps_estime_fabrication
				,'temps_reel_fabrication'=>$assetOf->temps_reel_fabrication
				
				,'note'=>$form->zonetexte('', 'note', $assetOf->note, 80,5)
				
				,'status'=>$form->combo('','status',TAssetOf::$TStatus,$assetOf->status)
				,'idChild' => (!empty($Tid)) ? '"'.implode('","',$Tid).'"' : ''
			)
			,'view'=>array(
				'mode'=>$mode
				,'status'=>$assetOf->status
				,'select_product'=>$select_product
				,'select_workstation'=>$form->combo('', 'fk_asset_workstation', TAssetWorkstation::getWorstations($PDOdb), -1)			
				,'actionChild'=>($mode == 'edit')?__get('actionChild','edit'):__get('actionChild','view')
			)
		)
	);
	
	echo $form->end_form();
	
	
	
	
	
	llxFooter('$Date: 2011/07/31 22:21:57 $ - $Revision: 1.19 $');
}
